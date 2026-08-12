<?php
/**
 * EduGrant — Admin: Applications Monitor
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

$statusFilter = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');

$where = ['1=1'];
$params = [];
if (in_array($statusFilter, ['interested', 'preparing', 'applied', 'under_review', 'approved', 'rejected'], true)) {
    $where[] = 'a.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(s.title LIKE ? OR sp.first_name LIKE ? OR sp.last_name LIKE ? OR p.organization_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}

$stmt = $pdo->prepare(
    'SELECT a.*, s.title AS scholarship_title, s.deadline, p.organization_name,
            sp.first_name, sp.last_name, sp.course, sp.school, u.email
     FROM applications a
     JOIN scholarships s ON s.id = a.scholarship_id
     JOIN provider_profiles p ON p.id = s.provider_id
     JOIN student_profiles sp ON sp.id = a.student_id
     JOIN users u ON u.id = sp.user_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY a.updated_at DESC'
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
                <h4 class="fw-bold text-navy mb-3">Applications Monitor</h4>

                <div class="card card-body py-3 mb-3">
                    <form method="get" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" placeholder="Search student, scholarship, provider..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All statuses</option>
                                <?php foreach (['interested', 'preparing', 'applied', 'under_review', 'approved', 'rejected'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $st)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary-soft btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/applications.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Scholarship</th>
                                    <th>Provider</th>
                                    <th>Status</th>
                                    <th>Application Date</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($applications)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No applications found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($applications as $a): $dl = getDeadlineState($a['deadline']); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($a['first_name'] . ' ' . $a['last_name']); ?></div>
                                                <small class="text-muted"><?php echo e($a['email']); ?></small>
                                            </td>
                                            <td><?php echo e($a['scholarship_title']); ?></td>
                                            <td><?php echo e($a['organization_name']); ?></td>
                                            <td><span class="badge badge-status bg-<?php echo e(appStatusBadge($a['status'])); ?>"><?php echo e(ucwords(str_replace('_', ' ', $a['status']))); ?></span></td>
                                            <td><?php echo e(formatDate($a['application_date'])); ?></td>
                                            <td>
                                                <?php if ($a['deadline']): ?>
                                                    <?php echo e($dl['deadline']); ?>
                                                    <div class="small text-muted"><?php echo e($dl['label']); ?></div>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
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

<?php include BASE_PATH . '/includes/footer.php'; ?>
