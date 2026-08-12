<?php
/**
 * EduGrant — Admin: Profile (account info + password change)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/profile.php');
    $current = (string)$_POST['current_password'] ?? '';
    $new = (string)$_POST['new_password'] ?? '';
    $confirm = (string)$_POST['confirm_password'] ?? '';

    $row = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $row->execute([$user['id']]);
    $hash = $row->fetchColumn();

    if (!password_verify($current, $hash)) {
        $_SESSION['flash_error'] = 'Your current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $_SESSION['flash_error'] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $_SESSION['flash_error'] = 'New passwords do not match.';
    } else {
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        logActivity($user['id'], 'Password change', 'Admin password changed.');
        $_SESSION['flash_success'] = 'Password changed successfully.';
    }
    redirect('admin/profile.php');
}

$page_title = 'My Profile';
$layout = 'dashboard';
$page_active = 'profile';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header"><i class="bi bi-person-badge me-1"></i>Administrator Account</div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><strong>Name:</strong> <?php echo e($user['name']); ?></li>
                                    <li class="mb-2"><strong>Email:</strong> <?php echo e($user['email']); ?></li>
                                    <li class="mb-2"><strong>Role:</strong> <span class="badge bg-dark">Admin</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><i class="bi bi-shield-lock me-1"></i>Change Password</div>
                            <div class="card-body">
                                <form method="post">
                                    <?php echo csrfField(); ?>
                                    <div class="mb-3">
                                        <label class="form-label required">Current password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label required">New password</label>
                                        <input type="password" class="form-control" name="new_password" minlength="8" required>
                                        <div class="form-text">At least 8 characters.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label required">Confirm new password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary-soft"><i class="bi bi-key me-1"></i>Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
