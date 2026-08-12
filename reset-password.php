<?php
/**
 * EduGrant — Reset Password
 *
 * Validates a one-time token from the password_resets table and lets the
 * user choose a new password.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$token = clean($_GET['token'] ?? ($_POST['token'] ?? ''));
$email = clean($_GET['email'] ?? ($_POST['email'] ?? ''));

$error = '';
$valid = false;
$userId = null;

function validateResetToken(PDO $pdo, string $token, string $email): ?int
{
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $row = $stmt->fetch();
    if (!$row || $row['used']) {
        return null;
    }
    if (strtotime($row['expires_at']) < time()) {
        return null;
    }
    $user = $pdo->prepare('SELECT id FROM users WHERE id = ? AND email = ?');
    $user->execute([$row['user_id'], $email]);
    return $user->fetch() ? (int)$row['user_id'] : null;
}

if ($token === '' || $email === '') {
    $error = 'Invalid or missing reset link.';
} else {
    $pdo = db();
    $userId = validateResetToken($pdo, $token, $email);
    if ($userId === null) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $valid = true;
    }
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('forgot-password.php');
    $pdo = db();

    $newPass  = (string)($_POST['new_password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    if (strlen($newPass) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($newPass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $up->execute([$hash, $userId]);

            $use = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?');
            $use->execute([$userId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Password reset error: ' . $e->getMessage());
            $error = 'Could not reset your password. Please try again.';
            $valid = false;
        }

        if ($error === '') {
            logActivity($userId, 'Password reset', 'Password was reset successfully.');
            $_SESSION['flash_success'] = 'Your password has been reset successfully. Please log in.';
            redirect('login.php?reset=ok');
        }
    }
}

$page_title = 'Reset Password';
$layout = 'auth';
include BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <div class="auth-card card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="display-6 text-navy"><i class="bi bi-shield-lock"></i></span>
                <h1 class="h3 fw-bold text-navy mb-1">Set a new password</h1>
                <p class="text-muted mb-0">For account: <strong><?php echo e($email); ?></strong></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($valid): ?>
                <form method="post" action="<?php echo url('reset-password.php'); ?>" novalidate>
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">
                    <input type="hidden" name="email" value="<?php echo e($email); ?>">
                    <div class="mb-3">
                        <label for="new_password" class="form-label required">New password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password"
                               minlength="8" required>
                        <div class="form-text">At least 8 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label required">Confirm new password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary-soft w-100 py-2 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Update Password
                    </button>
                </form>
            <?php else: ?>
                <a class="btn btn-primary-soft w-100" href="<?php echo url('forgot-password.php'); ?>">Request a new link</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
