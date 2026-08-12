<?php
/**
 * EduGrant — Student Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/deadline-notifications.php';

requireRole('student');

// Controlled deadline-notification maintenance (idempotent, deduplicated)
generateDeadlineNotifications();

$user  = getCurrentUser();
$me    = getStudentProfileByUserId($user['id']);
$pdo   = db();

// ---- Statistics (real database values) ----
$stmt = $pdo->prepare('SELECT COUNT(*) FROM saved_scholarships WHERE student_id = ?');
$stmt->execute([$me['id']]);
$savedCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE student_id = ?');
$stmt->execute([$me['id']]);
$appCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM saved_scholarships ss
     JOIN scholarships s ON s.id = ss.scholarship_id
     WHERE ss.student_id = ? AND s.deadline IS NOT NULL AND s.deadline >= CURDATE()'
);
$stmt->execute([$me['id']]);
$upcomingCount = (int)$stmt->fetchColumn();

// Upcoming deadlines from saved scholarships
$stmt = $pdo->prepare(
    'SELECT s.id, s.title, s.deadline, p.organization_name
     FROM saved_scholarships ss
     JOIN scholarships s ON s.id = ss.scholarship_id
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE ss.student_id = ? AND s.deadline IS NOT NULL AND s.deadline >= CURDATE()
     ORDER BY s.deadline ASC LIMIT 4'
);
$stmt->execute([$me['id']]);
$upcomingDeadlines = $stmt->fetchAll();

// Recommended scholarships (same education level / course keywords as student)
$recStmt = $pdo->query(
    "SELECT s.*, p.organization_name,
            EXISTS(
                SELECT 1 FROM saved_scholarships ss
                WHERE ss.student_id = {$me['id']} AND ss.scholarship_id = s.id
            ) AS is_saved
     FROM scholarships s
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE s.status = 'approved' AND (s.deadline IS NULL OR s.deadline >= CURDATE())
     ORDER BY s.created_at DESC
     LIMIT 4"
);
$recommended = $recStmt->fetchAll();

// Recent notifications
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 4');
$stmt->execute([$user['id']]);
$recentNotifications = $stmt->fetchAll();

// Recent applications
$stmt = $pdo->prepare(
    'SELECT a.*, s.title, s.deadline, p.organization_name
     FROM applications a
     JOIN scholarships s ON s.id = a.scholarship_id
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE a.student_id = ?
     ORDER BY a.updated_at DESC LIMIT 4'
);
$stmt->execute([$me['id']]);
$recentApplications = $stmt->fetchAll();

$page_title = 'Student Dashboard';
$layout = 'dashboard';
$page_active = 'dashboard';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h4 class="fw-bold text-navy mb-0">
                            Welcome back, <?php echo e($user['name']); ?>!
                        </h4>
                        <div class="text-muted">
                            <?php echo e($me['school'] ?? '—'); ?> · <?php echo e($me['course'] ?? '—'); ?> · <?php echo e($me['year_level'] ?? ''); ?>
                        </div>
                    </div>
                    <a class="btn btn-primary-soft" href="<?php echo url('student/scholarships.php'); ?>">
                        <i class="bi bi-search me-1"></i> Browse Scholarships
                    </a>
                </div>

                <!-- Stat cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-blue-soft"><i class="bi bi-bookmark-heart text-primary"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo $savedCount; ?></div>
                                    <div class="stat-label">Saved Scholarships</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-green-soft"><i class="bi bi-kanban text-success"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo $appCount; ?></div>
                                    <div class="stat-label">Active Applications</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-orange-soft"><i class="bi bi-alarm text-warning"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo $upcomingCount; ?></div>
                                    <div class="stat-label">Upcoming Deadlines</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-purple-soft"><i class="bi bi-star text-primary"></i></div>
                                <div>
                                    <div class="stat-value"><?php echo count($recommended); ?></div>
                                    <div class="stat-label">Recommended</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Recommended -->
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recommended Scholarships</span>
                                <a class="small text-decoration-none" href="<?php echo url('student/scholarships.php'); ?>">View all</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recommended)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-stars"></i>
                                        <p class="mb-0 mt-2">No recommendations yet. Check back soon.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recommended as $r): $dl = getDeadlineState($r['deadline']); ?>
                                        <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                                            <div class="min-w-120">
                                                <div class="fw-semibold"><?php echo e($r['title']); ?></div>
                                                <div class="small text-muted"><?php echo e($r['organization_name']); ?></div>
                                                <span class="badge badge-status bg-<?php echo e($dl['badge']); ?>"><?php echo e($dl['label']); ?></span>
                                            </div>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo url('student/scholarship-details.php?id=' . $r['id']); ?>">Details</a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming deadlines -->
                    <div class="col-lg-5">
                        <div class="card h-100">
                            <div class="card-header">Upcoming Deadlines</div>
                            <div class="card-body">
                                <?php if (empty($upcomingDeadlines)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-calendar-x"></i>
                                        <p class="mb-0 mt-2">No upcoming deadlines for your saved scholarships.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($upcomingDeadlines as $d): $dl = getDeadlineState($d['deadline']); ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <div class="fw-semibold small"><?php echo e($d['title']); ?></div>
                                                <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i><?php echo e($dl['deadline']); ?></div>
                                            </div>
                                            <span class="badge badge-status bg-<?php echo e($dl['badge']); ?>"><?php echo $dl['days_remaining'] >= 0 ? $dl['days_remaining'] . ' days' : 'Closed'; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent applications -->
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recent Applications</span>
                                <a class="small text-decoration-none" href="<?php echo url('student/applications.php'); ?>">View all</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Scholarship</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentApplications)): ?>
                                                <tr><td colspan="3" class="text-center text-muted py-4">No applications tracked yet.
                                                    <a href="<?php echo url('student/scholarships.php'); ?>" class="d-block small">Start tracking one</a></td></tr>
                                            <?php else: ?>
                                                <?php foreach ($recentApplications as $a): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold"><?php echo e($a['title']); ?></div>
                                                            <small class="text-muted"><?php echo e($a['organization_name']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php $label = ucwords(str_replace('_', ' ', $a['status'])); ?>
                                                            <span class="badge badge-status bg-<?php echo e(appStatusBadge($a['status'])); ?>"><?php echo e($label); ?></span>
                                                        </td>
                                                        <td>
                                                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('student/application-details.php?id=' . $a['id']); ?>">View</a>
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

                    <!-- Recent notifications -->
                    <div class="col-lg-5">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recent Notifications</span>
                                <a class="small text-decoration-none" href="<?php echo url('student/notifications.php'); ?>">View all</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentNotifications)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-bell"></i>
                                        <p class="mb-0 mt-2">You have no notifications yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentNotifications as $n): ?>
                                        <div class="d-flex gap-2 py-2 border-bottom">
                                            <i class="bi bi-<?php echo e(notifIcon($n['type'])); ?> text-primary"></i>
                                            <div>
                                                <div class="fw-semibold small"><?php echo e($n['title']); ?></div>
                                                <div class="small text-muted"><?php echo e($n['message']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
