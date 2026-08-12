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
    <link rel="icon" href="<?php echo url('assets/images/favicon.svg'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body class="<?php echo $layout === 'dashboard' ? 'dashboard-layout' : ''; ?>">

<?php require __DIR__ . '/navbar.php'; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>
