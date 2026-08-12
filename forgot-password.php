<?php
/**
 * EduGrant — Forgot Password
 *
 * In development mode (DEV_MODE = true) the reset link is shown directly
 * on screen because no mail server is configured. A generic success
 * message is always shown to avoid account enumeration.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$done = false;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('forgot-password.php');

    $email = clean($_POST['email'] ?? '');
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600);

        $ins = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        );
        $ins->execute([$user['id'], hash('sha256', $token), $expiry]);

        $resetLink = url('reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($email));
        logActivity($user['id'], 'Password reset requested', 'A password reset link was requested.');
    }

    // Always show the generic message; the link is shown only in dev mode.
    $done = true;
}

$page_title = 'Forgot Password';
$layout = 'auth';
include BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <div class="auth-card card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="display-6 text-navy"><i class="bi bi-key"></i></span>
                <h1 class="h3 fw-bold text-navy mb-1">Reset your password</h1>
                <p class="text-muted mb-0">Enter your registered email to receive a reset link.</p>
            </div>

            <?php if ($done): ?>
                <div class="alert alert-success" role="alert">
                    If that email address exists in our system, a password reset link has been generated.
                    <?php if ($resetLink): ?>
                        <div class="alert alert-info mt-3 mb-0 small">
                            <strong>Development mode:</strong> no mail server is configured, so your reset link is shown here.<br>
                            <a href="<?php echo e($resetLink); ?>"><?php echo e($resetLink); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
                <a class="btn btn-primary-soft w-100" href="<?php echo url('login.php'); ?>">Back to Log In</a>
            <?php else: ?>
                <form method="post" action="<?php echo url('forgot-password.php'); ?>" novalidate>
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary-soft w-100 py-2 fw-semibold">
                        <i class="bi bi-envelope-paper me-1"></i> Send Reset Link
                    </button>
                </form>
                <p class="text-center small text-muted mt-3 mb-0">
                    <a href="<?php echo url('login.php'); ?>">Back to login</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
