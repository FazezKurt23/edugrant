<?php
/**
 * EduGrant — Top Navigation Bar
 *
 * Rendered by header.php. Expected variables:
 *   $currentUser : array|false (from getCurrentUser())
 *   $notifCount  : int
 *   $notifList   : array
 *   $page_active : string
 */
?>
<nav class="navbar navbar-expand-lg navbar-dark edu-navbar sticky-top shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold" href="<?php echo url('index.php'); ?>">
            <i class="bi bi-mortarboard-fill me-1"></i><?php echo e(APP_NAME); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#eduNavbar"
                aria-controls="eduNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="eduNavbar">
            <?php if (!$currentUser): ?>
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link <?php echo $page_active === 'home' ? 'active' : ''; ?>" href="<?php echo url('index.php'); ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo url('index.php#scholarships'); ?>">Scholarships</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo url('index.php#how'); ?>">How It Works</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-outline-light btn-sm px-3 me-lg-2" href="<?php echo url('login.php'); ?>">Log In</a></li>
                    <li class="nav-item"><a class="btn btn-accent btn-sm px-3" href="<?php echo url('register.php'); ?>">Get Started</a></li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <?php if ($currentUser['role'] === 'student'): ?>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'dashboard' ? 'active' : ''; ?>" href="<?php echo url('student/dashboard.php'); ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'scholarships' ? 'active' : ''; ?>" href="<?php echo url('student/scholarships.php'); ?>">Scholarships</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'applications' ? 'active' : ''; ?>" href="<?php echo url('student/applications.php'); ?>">My Applications</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'saved' ? 'active' : ''; ?>" href="<?php echo url('student/saved-scholarships.php'); ?>">Saved</a></li>
                    <?php elseif ($currentUser['role'] === 'provider'): ?>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'dashboard' ? 'active' : ''; ?>" href="<?php echo url('provider/dashboard.php'); ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'scholarships' ? 'active' : ''; ?>" href="<?php echo url('provider/scholarships.php'); ?>">My Scholarships</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'applications' ? 'active' : ''; ?>" href="<?php echo url('provider/applications.php'); ?>">Applications</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link <?php echo $page_active === 'dashboard' ? 'active' : ''; ?>" href="<?php echo url('admin/dashboard.php'); ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('admin/users.php'); ?>">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo url('admin/scholarships.php'); ?>">Scholarships</a></li>
                    <?php endif; ?>

                    <li class="nav-item ms-lg-2">
                        <div class="dropdown">
                            <button class="btn btn-icon dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell"></i>
                                <?php if ($notifCount > 0): ?><span class="notif-badge"><?php echo $notifCount; ?></span><?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0">
                                <div class="notif-header px-3 py-2 d-flex justify-content-between align-items-center">
                                    <strong>Notifications</strong>
                                    <?php if ($notifCount > 0): ?>
                                        <form method="post" action="<?php echo url($currentUser['role'] . '/notifications.php'); ?>" class="d-inline">
                                            <?php echo csrfField(); ?>
                                            <button type="submit" name="mark_all_read" value="1" class="btn btn-link btn-sm p-0 text-decoration-none">Mark all read</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php if (empty($notifList)): ?>
                                    <div class="px-3 py-3 text-muted small">You have no new notifications.</div>
                                <?php else: ?>
                                    <?php foreach ($notifList as $n): ?>
                                        <a class="dropdown-item notif-item <?php echo $n['is_read'] ? 'read' : ''; ?>" href="<?php echo url($currentUser['role'] . '/notifications.php'); ?>">
                                            <div class="d-flex">
                                                <i class="bi bi-<?php echo e(notifIcon($n['type'])); ?> me-2 text-primary"></i>
                                                <div>
                                                    <div class="fw-semibold small"><?php echo e($n['title']); ?></div>
                                                    <div class="small text-muted text-truncate" style="max-width:240px;"><?php echo e($n['message']); ?></div>
                                                    <small class="text-muted"><?php echo e(timeAgo($n['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                    <a class="dropdown-item text-center small py-2" href="<?php echo url($currentUser['role'] . '/notifications.php'); ?>">View all notifications</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle user-chip" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i><?php echo e($currentUser['name']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header"><?php echo ucfirst($currentUser['role']); ?></h6></li>
                                <li><a class="dropdown-item" href="<?php echo url($currentUser['role'] . '/profile.php'); ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo url('logout.php'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
