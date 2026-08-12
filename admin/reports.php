<?php
/**
 * EduGrant — Admin: Reports & Activity Logs
 *
 * Provides summary statistics, activity log viewing, and CSV exports.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

// ---- CSV Export ----
if (isset($_GET['export'])) {
    $export = $_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="edugrant-' . $export . '-' . date('Ymd-His') . '.csv"');

    $out = fopen('php://output', 'w');

    if ($export === 'scholarships') {
        fputcsv($out, ['ID', 'Title', 'Provider', 'Type', 'Education Level', 'Location', 'Amount', 'Deadline', 'Status']);
        $rows = $pdo->query(
            'SELECT s.id, s.title, p.organization_name, s.scholarship_type, s.education_level,
                    s.location, s.amount, s.deadline, s.status
             FROM scholarships s JOIN provider_profiles p ON p.id = s.provider_id ORDER BY s.id'
        )->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, array_values($r));
        }
    } elseif ($export === 'users') {
        fputcsv($out, ['ID', 'Name', 'Email', 'Role', 'Status', 'Created']);
        $rows = $pdo->query('SELECT id, name, email, role, status, created_at FROM users ORDER BY id')->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, array_values($r));
        }
    } elseif ($export === 'applications') {
        fputcsv($out, ['Student', 'Scholarship', 'Status', 'Application Date', 'Last Updated']);
        $rows = $pdo->query(
            'SELECT CONCAT(sp.first_name, " ", sp.last_name), s.title, a.status, a.application_date, a.updated_at
             FROM applications a
             JOIN scholarships s ON s.id = a.scholarship_id
             JOIN student_profiles sp ON sp.id = a.student_id
             ORDER BY a.id'
        )->fetchAll();
        foreach ($rows as $r) {
            fputcsv($out, array_values($r));
        }
    }
    fclose($out);
    exit;
}

// Summary stats
$scholarshipsByStatus = $pdo->query(
    "SELECT status AS label, COUNT(*) AS c FROM scholarships GROUP BY status"
)->fetchAll();
$usersByRole = $pdo->query("SELECT role AS label, COUNT(*) AS c FROM users GROUP BY role")->fetchAll();
$applicationsByStatus = $pdo->query("SELECT status AS label, COUNT(*) AS c FROM applications GROUP BY status")->fetchAll();

$userFilter = clean($_GET['user_id'] ?? '');
$actionFilter = clean($_GET['action'] ?? '');

$where = ['1=1'];
$params = [];
if ($userFilter !== '') {
    $where[] = 'al.user_id = ?';
    $params[] = (int)$userFilter;
}
if ($actionFilter !== '') {
    $where[] = 'al.action = ?';
    $params[] = $actionFilter;
}

$stmt = $pdo->prepare(
    'SELECT al.*, u.name, u.email FROM activity_logs al
     LEFT JOIN users u ON u.id = al.user_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY al.created_at DESC, al.id DESC LIMIT 200'
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$actions = $pdo->query('SELECT DISTINCT action FROM activity_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Reports';
$layout = 'dashboard';
$page_active = 'reports';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-3">Reports</h4>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">Scholarships by Status</div>
                            <div class="card-body">
                                <?php if (empty($scholarshipsByStatus)): ?>
                                    <div class="text-muted small">No data.</div>
                                <?php else: ?>
                                    <?php $total = array_sum(array_column($scholarshipsByStatus, 'c')); ?>
                                    <?php foreach ($scholarshipsByStatus as $s): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><?php echo ucfirst($s['label']); ?></span>
                                            <strong><?php echo $s['c']; ?> (<?php echo $total > 0 ? round(($s['c'] / $total) * 100) : 0; ?>%)</strong>
                                        </div>
                                        <div class="progress mb-3" style="height:6px;">
                                            <div class="progress-bar bg-<?php echo e(scholarshipStatusBadge($s['label']) === 'warning text-dark' ? 'warning' : (scholarshipStatusBadge($s['label']) === 'dark' ? 'dark' : scholarshipStatusBadge($s['label']))); ?>" style="width:<?php echo $total > 0 ? round(($s['c'] / $total) * 100) : 0; ?>%"></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">Users by Role</div>
                            <div class="card-body">
                                <?php if (empty($usersByRole)): ?>
                                    <div class="text-muted small">No data.</div>
                                <?php else: ?>
                                    <?php $total = array_sum(array_column($usersByRole, 'c')); ?>
                                    <?php foreach ($usersByRole as $u): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><?php echo ucfirst($u['label']); ?></span>
                                            <strong><?php echo $u['c']; ?></strong>
                                        </div>
                                        <div class="progress mb-3" style="height:6px;">
                                            <div class="progress-bar bg-primary" style="width:<?php echo $total > 0 ? round(($u['c'] / $total) * 100) : 0; ?>%"></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header">Applications by Status</div>
                            <div class="card-body">
                                <?php if (empty($applicationsByStatus)): ?>
                                    <div class="text-muted small">No data.</div>
                                <?php else: ?>
                                    <?php $total = array_sum(array_column($applicationsByStatus, 'c')); ?>
                                    <?php foreach ($applicationsByStatus as $a): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><?php echo ucwords(str_replace('_', ' ', $a['label'])); ?></span>
                                            <strong><?php echo $a['c']; ?></strong>
                                        </div>
                                        <div class="progress mb-3" style="height:6px;">
                                            <div class="progress-bar bg-<?php echo e(appStatusBadge($a['label'])); ?>" style="width:<?php echo $total > 0 ? round(($a['c'] / $total) * 100) : 0; ?>%"></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><i class="bi bi-download me-1"></i>Export Data (CSV)</div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo url('admin/reports.php?export=scholarships'); ?>"><i class="bi bi-filetype-csv me-1"></i>Scholarships</a>
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo url('admin/reports.php?export=users'); ?>"><i class="bi bi-filetype-csv me-1"></i>Users</a>
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo url('admin/reports.php?export=applications'); ?>"><i class="bi bi-filetype-csv me-1"></i>Applications</a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-clock-history me-1"></i>Activity Logs</div>
                    <div class="card-body">
                        <form method="get" class="row g-2 mb-3">
                            <div class="col-md-4">
                                <select class="form-select form-select-sm" name="action">
                                    <option value="">All actions</option>
                                    <?php foreach ($actions as $ac): ?>
                                        <option value="<?php echo e($ac); ?>" <?php echo $actionFilter === $ac ? 'selected' : ''; ?>><?php echo e($ac); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm" name="user_id" placeholder="Filter by user ID" value="<?php echo e($userFilter); ?>">
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-sm btn-primary-soft">Filter</button>
                                <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/reports.php'); ?>">Reset</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Date</th><th>User</th><th>Action</th><th>Description</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">No activity logs match your filter.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td class="text-muted small" style="white-space:nowrap;"><?php echo e(formatDate($log['created_at'], 'M j, Y H:i')); ?></td>
                                                <td><?php echo $log['name'] ? e($log['name']) : '<span class="text-muted">System</span>'; ?></td>
                                                <td><span class="badge bg-light text-dark"><?php echo e($log['action']); ?></span></td>
                                                <td class="small"><?php echo e($log['description']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
