<?php
/**
 * EduGrant — My Applications (Student application tracking)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = getCurrentUser();
$me   = getStudentProfileByUserId($user['id']);
$pdo  = db();

$statusFilter = clean($_GET['status'] ?? '');

$where = 'a.student_id = ?';
$params = [$me['id']];
if ($statusFilter !== '' && in_array($statusFilter, ['interested', 'preparing', 'applied', 'under_review', 'approved', 'rejected'], true)) {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare(
    "SELECT a.*, s.title, s.deadline, s.official_url, p.organization_name
     FROM applications a
     JOIN scholarships s ON s.id = a.scholarship_id
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE $where
     ORDER BY a.updated_at DESC"
);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$page_title = 'My Applications';
$layout = 'dashboard';
$page_active = 'applications';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="fw-bold text-navy mb-0">My Applications</h4>
                        <p class="text-muted mb-0">Track your application progress for each scholarship.</p>
                    </div>
                    <a class="btn btn-primary-soft" href="<?php echo url('student/scholarships.php'); ?>"><i class="bi bi-search me-1"></i>Find More Scholarships</a>
                </div>

                <div class="card card-body mb-4 py-2">
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
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('student/applications.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <?php if (empty($applications)): ?>
                    <div class="card empty-state">
                        <i class="bi bi-kanban"></i>
                        <h5 class="mt-3">No applications tracked</h5>
                        <p>Use the "Track Application" button on any scholarship to begin.</p>
                        <div><a class="btn btn-primary-soft mt-2" href="<?php echo url('student/scholarships.php'); ?>">Browse Scholarships</a></div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Scholarship</th>
                                        <th>Provider</th>
                                        <th>Application Date</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $a): $dl = getDeadlineState($a['deadline']); ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo e($a['title']); ?></td>
                                            <td><?php echo e($a['organization_name']); ?></td>
                                            <td><?php echo e(formatDate($a['application_date'])); ?></td>
                                            <td>
                                                <?php if ($a['deadline']): ?>
                                                    <?php echo e($dl['deadline']); ?>
                                                    <div class="small text-muted"><?php echo $dl['days_remaining'] >= 0 ? $dl['days_remaining'] . ' days left' : 'Expired'; ?></div>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
                                            <td><span class="badge badge-status bg-<?php echo e(appStatusBadge($a['status'])); ?>"><?php echo e(ucwords(str_replace('_', ' ', $a['status']))); ?></span></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo url('student/application-details.php?id=' . $a['id']); ?>">Manage</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="alert alert-light border small mt-4 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    EduGrant is an <strong>information and application tracking system</strong>. It does not submit
                    applications to scholarship providers. Use the official application link provided for each scholarship.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
