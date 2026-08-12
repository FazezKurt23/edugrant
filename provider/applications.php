<?php
/**
 * EduGrant — Provider: Tracked Applications
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('provider');

$user = getCurrentUser();
$me   = getProviderProfileByUserId($user['id']);
$pdo  = db();

$statusFilter = clean($_GET['status'] ?? '');

$where = 's.provider_id = ?';
$params = [$me['id']];
if (in_array($statusFilter, ['interested', 'preparing', 'applied', 'under_review', 'approved', 'rejected'], true)) {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare(
    "SELECT a.*, s.title, s.deadline, sp.first_name, sp.last_name, sp.course, sp.school, sp.contact_number, u.email
     FROM applications a
     JOIN scholarships s ON s.id = a.scholarship_id
     JOIN student_profiles sp ON sp.id = a.student_id
     LEFT JOIN users u ON u.id = sp.user_id
     WHERE $where
     ORDER BY a.updated_at DESC"
);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$page_title = 'Applications';
$layout = 'dashboard';
$page_active = 'applications';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-1">Tracked Applications</h4>
                <p class="text-muted">Monitor how students track their application progress for your scholarships.</p>

                <div class="card card-body py-2 mb-3">
                    <form method="get" class="row g-2 align-items-center">
                        <div class="col-8 col-md-4 col-lg-3">
                            <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                <?php foreach (['interested', 'preparing', 'applied', 'under_review', 'approved', 'rejected'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $st)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('provider/applications.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <?php if (empty($applications)): ?>
                    <div class="card empty-state">
                        <i class="bi bi-kanban"></i>
                        <h5 class="mt-3">No tracked applications</h5>
                        <p class="mb-0">When students start tracking applications to your scholarships, they will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Scholarship</th>
                                        <th>Status</th>
                                        <th>Application Date</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $a): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($a['first_name'] . ' ' . $a['last_name']); ?></div>
                                                <small class="text-muted"><?php echo e($a['course'] ?: '—'); ?> · <?php echo e($a['school'] ?: ''); ?></small>
                                            </td>
                                            <td><?php echo e($a['title']); ?></td>
                                            <td><span class="badge badge-status bg-<?php echo e(appStatusBadge($a['status'])); ?>"><?php echo e(ucwords(str_replace('_', ' ', $a['status']))); ?></span></td>
                                            <td><?php echo e(formatDate($a['application_date'])); ?></td>
                                            <td><?php echo e(formatDate($a['updated_at'], 'M j, Y')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-light border small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        EduGrant tracking statuses are <strong>student-managed records</strong>. They reflect the student's
                        own progress, not the provider's official decision. Contact students directly using the official
                        application process.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
