<?php
/**
 * EduGrant — Page Header (open + navbar)
 *
 * Expected variables (optional):
 *   $page_title   : <title> text
 *   $page_active  : active nav item key
 *   $layout       : 'public' (default) or 'dashboard' (adds body class)
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? APP_NAME;
$page_active = $page_active ?? '';
$layout = $layout ?? 'public';

$page_description = $page_description ?? APP_TAGLINE . ' ' . APP_NAME . ' helps students discover scholarship opportunities, understand eligibility requirements, track applications, and stay informed about important deadlines.';
$page_url = BASE_URL . strtok($_SERVER['REQUEST_URI'], '?');

$currentUser = getCurrentUser();
$notifCount = 0;
$notifList = [];
if ($currentUser) {
    $notifCount = getUnreadNotificationCount($currentUser['id']);
    $notifList = getUnreadNotifications($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> — <?php echo e(APP_NAME); ?></title>

    <!-- SEO -->
    <meta name="description" content="<?php echo e($page_description); ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0d1b2a">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo e(APP_NAME); ?>">
    <meta property="og:title" content="<?php echo e($page_title); ?> — <?php echo e(APP_NAME); ?>">
    <meta property="og:description" content="<?php echo e($page_description); ?>">
    <meta property="og:url" content="<?php echo e($page_url); ?>">
    <meta property="og:image" content="<?php echo url('assets/images/favicon.svg'); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo e($page_title); ?> — <?php echo e(APP_NAME); ?>">
    <meta name="twitter:description" content="<?php echo e($page_description); ?>">

    <!-- Structured data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "<?php echo e(APP_NAME); ?>",
        "url": "<?php echo e(BASE_URL); ?>",
        "description": "<?php echo e(APP_TAGLINE); ?>",
        "applicationCategory": "EducationalApplication",
        "operatingSystem": "Web"
    }
    </script>

    <link rel="icon" href="<?php echo url('assets/images/favicon.svg'); ?>">

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css?v=' . (@filemtime(BASE_PATH . '/assets/css/style.css') ?: '1')); ?>">
</head>
<body class="<?php echo $layout === 'dashboard' ? 'dashboard-layout' : ($layout === 'auth' ? 'auth-page' : ($layout === 'public' ? 'public-layout' : '')); ?>">

<a class="skip-link" href="#main-content">Skip to main content</a>

<?php require __DIR__ . '/navbar.php'; ?>

<main id="main-content" tabindex="-1">

<?php if (!empty($_SESSION['flash_error']) || !empty($_SESSION['flash_success'])): ?>
    <div class="flash-toast-container" aria-live="polite">
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash-toast flash-toast-error" role="alert">
                <div class="flash-toast-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="flash-toast-body"><?php echo e($_SESSION['flash_error']); ?></div>
                <button type="button" class="flash-toast-close" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="flash-toast flash-toast-success" role="status">
                <div class="flash-toast-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="flash-toast-body"><?php echo e($_SESSION['flash_success']); ?></div>
                <button type="button" class="flash-toast-close" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                <?php unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
