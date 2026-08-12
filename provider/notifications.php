<?php
/**
 * EduGrant — Notifications (shared pattern for student/provider/admin)
 * Uses $role to build correct redirect paths.
 */
$role = basename(dirname($_SERVER['SCRIPT_NAME']));
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = getCurrentUser();
if ($user['role'] !== $role) {
    http_response_code(403);
    die('403 Forbidden');
}
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf($role . '/notifications.php');

    if (isset($_POST['mark_all_read'])) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        $_SESSION['flash_success'] = 'All notifications marked as read.';
    } elseif (isset($_POST['mark_read'])) {
        $nid = (int)$_POST['mark_read'];
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->execute([$nid, $user['id']]);
    } elseif (isset($_POST['delete'])) {
        $nid = (int)$_POST['delete'];
        $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$nid, $user['id']]);
    }

    redirect($role . '/notifications.php');
}

$typeFilter = clean($_GET['type'] ?? '');
$allowedTypes = ['deadline', 'application', 'scholarship', 'system', 'approval', 'rejection'];

$where = 'n.user_id = ?';
$params = [$user['id']];
if ($typeFilter !== '' && in_array($typeFilter, $allowedTypes, true)) {
    $where .= ' AND n.type = ?';
    $params[] = $typeFilter;
}

$stmt = $pdo->prepare("SELECT n.* FROM notifications n WHERE $where ORDER BY n.created_at DESC, n.id DESC LIMIT 100");
$stmt->execute($params);
$notifications = $stmt->fetchAll();

$page_title = 'Notifications';
$layout = 'dashboard';
$page_active = 'notifications';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="fw-bold text-navy mb-0">Notifications</h4>
                        <p class="text-muted mb-0">Stay informed about deadlines, approvals, and system updates.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="get" class="d-flex gap-2">
                            <select class="form-select form-select-sm" name="type" onchange="this.form.submit()">
                                <option value="">All types</option>
                                <?php foreach ($allowedTypes as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $typeFilter === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <form method="post">
                            <?php echo csrfField(); ?>
                            <button class="btn btn-primary-soft btn-sm" name="mark_all_read" value="1">
                                <i class="bi bi-check-all me-1"></i>Mark All Read
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="card empty-state">
                        <i class="bi bi-bell"></i>
                        <h5 class="mt-3">No notifications</h5>
                        <p class="mb-0">You have no notifications to display.</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($notifications as $n): ?>
                                <li class="list-group-item <?php echo $n['is_read'] ? '' : 'bg-white'; ?> d-flex justify-content-between align-items-start gap-2 py-3">
                                    <div class="d-flex gap-3">
                                        <span class="stat-icon bg-blue-soft"><i class="bi bi-<?php echo e(notifIcon($n['type'])); ?> text-primary"></i></span>
                                        <div>
                                            <div class="fw-semibold">
                                                <?php echo e($n['title']); ?>
                                                <?php if (!$n['is_read']): ?><span class="badge bg-primary ms-1">New</span><?php endif; ?>
                                            </div>
                                            <div class="small text-muted"><?php echo e($n['message']); ?></div>
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-clock me-1"></i><?php echo e(timeAgo($n['created_at'])); ?>
                                                · <span class="text-uppercase" style="font-size:.7rem;"><?php echo e($n['type']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-1">
                                        <?php if (!$n['is_read']): ?>
                                            <form method="post">
                                                <?php echo csrfField(); ?>
                                                <button class="btn btn-sm btn-outline-primary" name="mark_read" value="<?php echo $n['id']; ?>">
                                                    <i class="bi bi-check2 me-1"></i>Mark Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post">
                                            <?php echo csrfField(); ?>
                                            <button class="btn btn-sm btn-outline-danger" name="delete" value="<?php echo $n['id']; ?>">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
