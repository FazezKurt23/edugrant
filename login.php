<?php
/**
 * EduGrant — Login (Fixed Validation & Redirection)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Kon naka-login na, i-redirect sa saktong dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        redirect('provider/dashboard.php'); // Temporaryo nga ruta sa admin samtang walay admin polder
    } else {
        redirect($user['role'] . '/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('login.php');

    $email    = trim(clean($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $result = attemptLogin($email, $password);
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
            redirect($result['redirect']);
        }
        $error = $result['message'];
    }
}

$page_title = 'Log In';
$layout = 'auth';
include BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <div class="auth-card card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="display-6 text-navy"><i class="bi bi-mortarboard-fill"></i></span>
                <h1 class="h3 fw-bold text-navy mb-1">Welcome to <?php echo e(APP_NAME); ?></h1>
                <p class="text-muted mb-0">Log in to continue your scholarship journey.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['blocked'])): ?>
                <div class="alert alert-warning" role="alert">Your account is not active. Please contact the administrator.</div>
            <?php endif; ?>
            <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok'): ?>
                <div class="alert alert-success" role="alert">Your password has been reset. You can now log in.</div>
            <?php endif; ?>

            <form method="post" action="<?php echo url('login.php'); ?>" novalidate>
                <?php echo csrfField(); ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo e($_POST['email'] ?? ''); ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="<?php echo url('forgot-password.php'); ?>" class="small">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-primary-soft w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted small mb-1">New to <?php echo e(APP_NAME); ?>?
                <a href="<?php echo url('register.php'); ?>">Create an account</a>
            </p>
            <p class="text-center text-muted small mb-0">
                Demo accounts — student@edugrant.local · provider@edugrant.local · admin@edugrant.local
            </p>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>