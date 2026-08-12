<?php
/**
 * EduGrant — Admin: Students Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/students.php');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'set_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['active', 'inactive', 'suspended'], true)) {
            $pdo->prepare('UPDATE users SET status = ? WHERE id = ? AND role = "student"')->execute([$status, $id]);
            logActivity($user['id'], 'Student status update', 'Set student user #' . $id . ' status to ' . $status . '.');
            $_SESSION['flash_success'] = 'Student status updated.';
        }
    }
    redirect('admin/students.php' . (isset($_POST['return_view']) ? '?view=' . (int)$_POST['return_view'] : ''));
}

$search = clean($_GET['q'] ?? '');
$viewId = (int)($_GET['view'] ?? 0);

$where = ['u.role = "student"'];
$params = [];
if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR sp.student_id LIKE ? OR sp.school LIKE ? OR sp.course LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

$stmt = $pdo->prepare(
    'SELECT u.id AS user_id, u.name, u.email, u.status, u.created_at, sp.*
     FROM users u
     LEFT JOIN student_profiles sp ON sp.user_id = u.id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY u.created_at DESC'
);
$stmt->execute($params);
$students = $stmt->fetchAll();

$viewStudent = null;
if ($viewId) {
    $vs = $pdo->prepare(
        'SELECT u.id AS user_id, u.name, u.email, u.status, u.created_at, sp.*
         FROM users u LEFT JOIN student_profiles sp ON sp.user_id = u.id
         WHERE u.id = ? AND u.role = "student"'
    );
    $vs->execute([$viewId]);
    $viewStudent = $vs->fetch() ?: null;
}

$page_title = 'Students';
$layout = 'dashboard';
$page_active = 'students';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-3">Students</h4>

                <?php if ($viewStudent): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-badge me-1"></i>Student Profile</span>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/students.php' . ($search !== '' ? '?q=' . urlencode($search) : '')); ?>">Close</a>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="text-navy"><?php echo e($viewStudent['name']); ?></h6>
                                    <div class="text-muted small">
                                        Student ID: <?php echo e($viewStudent['student_id'] ?? '—'); ?><br>
                                        Email: <?php echo e($viewStudent['email']); ?><br>
                                        Contact: <?php echo e($viewStudent['contact_number'] ?? '—'); ?><br>
                                        Birth date: <?php echo e(formatDate($viewStudent['birth_date'])); ?><br>
                                        Gender: <?php echo e($viewStudent['gender'] ?? '—'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">
                                        School: <?php echo e($viewStudent['school'] ?? '—'); ?><br>
                                        Course: <?php echo e($viewStudent['course'] ?? '—'); ?><br>
                                        Year level: <?php echo e($viewStudent['year_level'] ?? '—'); ?><br>
                                        GPA: <?php echo e($viewStudent['gpa'] ?? '—'); ?><br>
                                        Family income: <?php echo $viewStudent['family_income'] !== null ? 'PHP ' . number_format((float)$viewStudent['family_income'], 2) : '—'; ?><br>
                                        Address: <?php echo e(trim(($viewStudent['address'] ?? '') . ', ' . ($viewStudent['city'] ?? '') . ', ' . ($viewStudent['province'] ?? ''), ', ')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card card-body py-3 mb-3">
                    <form method="get" class="row g-2">
                        <div class="col-md-6 col-lg-5">
                            <input type="text" class="form-control" name="q" placeholder="Search name, email, ID, school, course..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary-soft btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/students.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>School / Course</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No students found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($s['name']); ?></div>
                                                <small class="text-muted"><?php echo e($s['email']); ?></small>
                                            </td>
                                            <td><?php echo e($s['student_id'] ?? '—'); ?></td>
                                            <td>
                                                <?php echo e($s['course'] ?: '—'); ?>
                                                <div class="small text-muted"><?php echo e($s['school'] ?? ''); ?></div>
                                            </td>
                                            <td><span class="badge <?php echo $s['status'] === 'active' ? 'bg-success' : ($s['status'] === 'suspended' ? 'bg-danger' : 'bg-secondary'); ?>"><?php echo e($s['status']); ?></span></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo url('admin/students.php?view=' . $s['user_id'] . ($search !== '' ? '&q=' . urlencode($search) : '')); ?>">View</a>
                                                <form method="post" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="id" value="<?php echo $s['user_id']; ?>">
                                                    <input type="hidden" name="return_view" value="<?php echo $viewId; ?>">
                                                    <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                        <option value="active" <?php echo $s['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $s['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        <option value="suspended" <?php echo $s['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                    </select>
                                                </form>
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
