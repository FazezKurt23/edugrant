<?php
/**
 * EduGrant — Admin Dashboard
 *
 * Real statistics + Chart.js visualizations. Also runs the controlled
 * "expire past deadline scholarships" maintenance.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

// Controlled maintenance: flag approved scholarships past their deadline as expired
expirePastDeadlineScholarships();

// ---- Statistics ----
function countCol(string $sql): int
{
    $pdo = db();
    return (int)$pdo->query($sql)->fetchColumn();
}

$totalStudents  = countCol("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalProviders = countCol("SELECT COUNT(*) FROM users WHERE role = 'provider'");
$totalScholars  = countCol('SELECT COUNT(*) FROM scholarships');
$pendingScholars = countCol("SELECT COUNT(*) FROM scholarships WHERE status = 'pending'");
$approvedScholars = countCol("SELECT COUNT(*) FROM scholarships WHERE status = 'approved'");
$totalApps      = countCol('SELECT COUNT(*) FROM applications');
$upcomingDeadlines = countCol('SELECT COUNT(*) FROM scholarships WHERE deadline >= CURDATE() AND status = \'approved\'');
$totalUsers     = countCol('SELECT COUNT(*) FROM users');

// ---- Chart data ----
$scholarshipsByType = $pdo->query(
    "SELECT COALESCE(NULLIF(scholarship_type, ''), 'General') AS label, COUNT(*) AS c
     FROM scholarships GROUP BY label ORDER BY c DESC"
)->fetchAll();

$scholarshipsByLevel = $pdo->query(
    "SELECT COALESCE(NULLIF(education_level, ''), 'All') AS label, COUNT(*) AS c
     FROM scholarships GROUP BY label ORDER BY c DESC"
)->fetchAll();

$appsByStatus = $pdo->query(
    'SELECT status AS label, COUNT(*) AS c FROM applications GROUP BY status ORDER BY c DESC'
)->fetchAll();

$submissionsByMonth = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%b %Y') AS label, COUNT(*) AS c
     FROM scholarships GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY MIN(created_at)"
)->fetchAll();

function chartJson(array $rows, string $labelKey, string $valueKey): string
{
    $labels = [];
    $data = [];
    foreach ($rows as $r) {
        $labels[] = $r[$labelKey];
        $data[] = (int)$r[$valueKey];
    }
    return json_encode(['labels' => $labels, 'data' => $data]);
}

$page_title = 'Admin Dashboard';
$layout = 'dashboard';
$page_active = 'dashboard';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-4">Admin Dashboard</h4>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-blue-soft"><i class="bi bi-mortarboard text-primary"></i></div>
                                <div><div class="stat-value"><?php echo $totalStudents; ?></div><div class="stat-label">Total Students</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-purple-soft"><i class="bi bi-buildings text-primary"></i></div>
                                <div><div class="stat-value"><?php echo $totalProviders; ?></div><div class="stat-label">Total Providers</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-green-soft"><i class="bi bi-collection text-success"></i></div>
                                <div><div class="stat-value"><?php echo $totalScholars; ?></div><div class="stat-label">Total Scholarships</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-orange-soft"><i class="bi bi-hourglass-split text-warning"></i></div>
                                <div><div class="stat-value"><?php echo $pendingScholars; ?></div><div class="stat-label">Pending Scholarships</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-green-soft"><i class="bi bi-check-circle text-success"></i></div>
                                <div><div class="stat-value"><?php echo $approvedScholars; ?></div><div class="stat-label">Approved</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-blue-soft"><i class="bi bi-kanban text-primary"></i></div>
                                <div><div class="stat-value"><?php echo $totalApps; ?></div><div class="stat-label">Total Applications</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-red-soft"><i class="bi bi-alarm text-danger"></i></div>
                                <div><div class="stat-value"><?php echo $upcomingDeadlines; ?></div><div class="stat-label">Upcoming Deadlines</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-orange-soft"><i class="bi bi-people text-warning"></i></div>
                                <div><div class="stat-value"><?php echo $totalUsers; ?></div><div class="stat-label">Total Users</div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Scholarships by Type</div>
                            <div class="card-body"><canvas id="chartType" height="240"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Applications by Status</div>
                            <div class="card-body"><canvas id="chartStatus" height="240"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Scholarships by Education Level</div>
                            <div class="card-body"><canvas id="chartLevel" height="240"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Scholarship Submissions by Month</div>
                            <div class="card-body"><canvas id="chartMonth" height="240"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeData = <?php echo chartJson($scholarshipsByType, 'label', 'c'); ?>;
    const statusData = <?php echo chartJson($appsByStatus, 'label', 'c'); ?>;
    const levelData = <?php echo chartJson($scholarshipsByLevel, 'label', 'c'); ?>;
    const monthData = <?php echo chartJson($submissionsByMonth, 'label', 'c'); ?>;

    function mkBar(canvasId, data, label) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{ label: label, data: data.data, backgroundColor: '#2563eb', borderRadius: 6 }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
    function mkPie(canvasId, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;
        const palette = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#7c3aed', '#0ea5e9', '#64748b'];
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{ data: data.data, backgroundColor: data.labels.map((_, i) => palette[i % palette.length]) }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    mkBar('chartType', typeData, 'Scholarships');
    mkBar('chartLevel', levelData, 'Scholarships');
    mkBar('chartMonth', monthData, 'Submissions');
    mkPie('chartStatus', statusData);
});
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
