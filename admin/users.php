<?php
/**
 * EduGrant — Admin: User Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/users.php');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id === $user['id']) {
        $_SESSION['flash_error'] = 'You cannot modify your own account from this page.';
    } elseif ($action === 'set_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['active', 'inactive', 'suspended'], true)) {
            // Prevent removing the last active admin
            if ($status !== 'active') {
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
                $check->execute();
                $activeAdmins = (int)$check->fetchColumn();
                $target = $pdo->prepare('SELECT role, status FROM users WHERE id = ?');
                $target->execute([$id]);
                $targetUser = $target->fetch();
                if ($targetUser && $targetUser['role'] === 'admin' && $activeAdmins <= 1) {
                    $_SESSION['flash_error'] = 'Cannot deactivate the last active administrator account.';
                    redirect('admin/users.php');
                }
            }
            $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            logActivity($user['id'], 'User status update', 'Set user #' . $id . ' status to ' . $status . '.');
            $_SESSION['flash_success'] = 'User status updated to "' . $status . '".';
        }
    } elseif ($action === 'delete') {
        $target = $pdo->prepare('SELECT role, status FROM users WHERE id = ?');
        $target->execute([$id]);
        $targetUser = $target->fetch();
        if ($targetUser && $targetUser['role'] === 'admin') {
            $_SESSION['flash_error'] = 'Administrator accounts cannot be deleted.';
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            logActivity($user['id'], 'User deletion', 'Deleted user #' . $id . '.');
            $_SESSION['flash_success'] = 'User deleted.';
        }
    }
    redirect('admin/users.php');
}

$search = clean($_GET['q'] ?? '');
$roleFilter = clean($_GET['role'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like);
}
if (in_array($roleFilter, ['student', 'provider', 'admin'], true)) {
    $where[] = 'u.role = ?';
    $params[] = $roleFilter;
}
if (in_array($statusFilter, ['active', 'inactive', 'suspended'], true)) {
    $where[] = 'u.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare('SELECT * FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.created_at DESC');
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'User Management';
$layout = 'dashboard';
$page_active = 'users';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-3">All Users</h4>

                <div class="card card-body py-3 mb-3">
                    <form method="get" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" placeholder="Search by name or email..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="role">
                                <option value="">All roles</option>
                                <?php foreach (['student', 'provider', 'admin'] as $r): ?>
                                    <option <?php echo $roleFilter === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All statuses</option>
                                <?php foreach (['active', 'inactive', 'suspended'] as $s): ?>
                                    <option <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary-soft btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/users.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($u['name']); ?></div>
                                            <small class="text-muted"><?php echo e($u['email']); ?></small>
                                        </td>
                                        <td><span class="badge <?php echo $u['role'] === 'admin' ? 'bg-dark' : ($u['role'] === 'provider' ? 'bg-info' : 'bg-primary'); ?>"><?php echo e($u['role']); ?></span></td>
                                        <td>
                                            <span class="badge <?php echo $u['status'] === 'active' ? 'bg-success' : ($u['status'] === 'suspended' ? 'bg-danger' : 'bg-secondary'); ?>"><?php echo e($u['status']); ?></span>
                                        </td>
                                        <td class="text-muted small"><?php echo e(formatDate($u['created_at'], 'M j, Y')); ?></td>
                                        <td class="text-end">
                                            <?php if ($u['id'] !== $user['id']): ?>
                                                <div class="d-inline-flex gap-1 flex-wrap">
                                                    <?php if ($u['status'] !== 'active'): ?>
                                                        <form method="post">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="set_status">
                                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="status" value="active">
                                                            <button class="btn btn-sm btn-outline-success" title="Activate">Activate</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($u['status'] !== 'suspended'): ?>
                                                        <form method="post">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="set_status">
                                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="status" value="suspended">
                                                            <button class="btn btn-sm btn-outline-warning" title="Suspend">Suspend</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($u['status'] !== 'inactive'): ?>
                                                        <form method="post">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="set_status">
                                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="status" value="inactive">
                                                            <button class="btn btn-sm btn-outline-secondary" title="Deactivate">Deactivate</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($u['role'] !== 'admin'): ?>
                                                        <form method="post" onsubmit="return confirm('Delete this user and all related records? This cannot be undone.');">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">(you)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
