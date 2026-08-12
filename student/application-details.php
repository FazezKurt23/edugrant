<?php
/**
 * EduGrant — Application Details (Student tracking)
 *
 * Allows the student to advance their tracking status and update notes.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = getCurrentUser();
$me   = getStudentProfileByUserId($user['id']);
$pdo  = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT a.*, s.title, s.description, s.deadline, s.official_url, p.organization_name
     FROM applications a
     JOIN scholarships s ON s.id = a.scholarship_id
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE a.id = ? AND a.student_id = ?'
);
$stmt->execute([$id, $me['id']]);
$app = $stmt->fetch();

if (!$app) {
    http_response_code(404);
    $page_title = 'Not Found';
    $layout = 'dashboard';
    $page_active = 'applications';
    include BASE_PATH . '/includes/header.php';
    echo '<div class="container py-5"><div class="card empty-state"><i class="bi bi-exclamation-triangle"></i><h5 class="mt-3">Application not found</h5><a class="btn btn-primary-soft mt-2" href="' . url('student/applications.php') . '">Back to My Applications</a></div></div>';
    include BASE_PATH . '/includes/footer.php';
    exit;
}

// Allowed next steps through the workflow
$workflow = [
    'interested'  => ['preparing'],
    'preparing'   => ['applied'],
    'applied'     => ['under_review'],
    'under_review'=> ['approved', 'rejected'],
    'approved'    => [],
    'rejected'    => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('student/application-details.php?id=' . $id);

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['new_status'] ?? '';
        if (in_array($newStatus, $workflow[$app['status']] ?? [], true)) {
            $stmt = $pdo->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ? AND student_id = ?');
            $stmt->execute([$newStatus, $id, $me['id']]);
            logActivity($user['id'], 'Application status update',
                'Updated "' . $app['title'] . '" status to "' . str_replace('_', ' ', $newStatus) . '".');
            $_SESSION['flash_success'] = 'Application status updated to "' . ucwords(str_replace('_', ' ', $newStatus)) . '".';
        } else {
            $_SESSION['flash_error'] = 'Invalid status change for this application.';
        }
    } elseif ($action === 'update_notes') {
        $notes = clean($_POST['notes'] ?? '');
        $stmt = $pdo->prepare('UPDATE applications SET notes = ?, updated_at = NOW() WHERE id = ? AND student_id = ?');
        $stmt->execute([$notes, $id, $me['id']]);
        logActivity($user['id'], 'Application note update', 'Updated notes for "' . $app['title'] . '".');
        $_SESSION['flash_success'] = 'Notes updated successfully.';
    }

    redirect('student/application-details.php?id=' . $id);
}

$dl = getDeadlineState($app['deadline']);
$allowedNext = $workflow[$app['status']] ?? [];

$page_title = 'Application Details';
$layout = 'dashboard';
$page_active = 'applications';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo url('student/applications.php'); ?>">My Applications</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo e($app['title']); ?></li>
                    </ol>
                </nav>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="fw-bold text-navy mb-1"><?php echo e($app['title']); ?></h4>
                        <div class="text-muted mb-3"><i class="bi bi-building me-1"></i><?php echo e($app['organization_name']); ?></div>

                        <!-- Progress tracker -->
                        <div class="progress-track">
                            <?php $flow = ['interested', 'preparing', 'applied', 'under_review']; ?>
                            <?php foreach ($flow as $step): ?>
                                <?php
                                $stepIdx = array_search($app['status'], array_merge($flow, ['approved', 'rejected']), true);
                                $curIdx = array_search($step, $flow, true);
                                $done = $stepIdx !== false && $curIdx < $stepIdx;
                                $current = $step === $app['status'];
                                ?>
                                <span class="track-step <?php echo $done ? 'done' : ($current ? 'current' : ''); ?>">
                                    <i class="bi bi-<?php echo $done ? 'check-lg' : 'circle'; ?>"></i><?php echo ucwords(str_replace('_', ' ', $step)); ?>
                                </span>
                                <?php if ($step !== 'under_review'): ?><i class="bi bi-chevron-right text-muted"></i><?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (in_array($app['status'], ['approved', 'rejected'], true)): ?>
                                <i class="bi bi-chevron-right text-muted"></i>
                                <span class="track-step <?php echo $app['status'] === 'approved' ? 'done' : ''; ?>">
                                    <i class="bi bi-<?php echo $app['status'] === 'approved' ? 'check-circle' : 'x-circle'; ?>"></i>
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <div class="row g-3">
                            <div class="col-md-4"><div class="meta-row"><i class="bi bi-calendar-check"></i><strong>Application date:</strong> <?php echo e(formatDate($app['application_date'])); ?></div></div>
                            <div class="col-md-4"><div class="meta-row"><i class="bi bi-alarm"></i><strong>Deadline:</strong> <?php echo $app['deadline'] ? e($dl['deadline']) . ' (' . ($dl['days_remaining'] >= 0 ? $dl['days_remaining'] . ' days left' : 'expired') . ')' : '—'; ?></div></div>
                            <div class="col-md-4"><div class="meta-row"><i class="bi bi-flag"></i><strong>Status:</strong> <span class="badge badge-status bg-<?php echo e(appStatusBadge($app['status'])); ?>"><?php echo e(ucwords(str_replace('_', ' ', $app['status']))); ?></span></div></div>
                        </div>

                        <?php if (!empty($app['official_url'])): ?>
                            <div class="mt-3">
                                <a class="btn btn-accent btn-sm" target="_blank" rel="noopener noreferrer" href="<?php echo e($app['official_url']); ?>">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Official Application Website
                                </a>
                                <div class="small text-muted mt-1">You will be redirected to the official scholarship application website.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Update Status</div>
                            <div class="card-body">
                                <?php if (empty($allowedNext)): ?>
                                    <div class="alert alert-info mb-0">
                                        This application has reached its final state (<?php echo ucfirst($app['status']); ?>).
                                        No further status changes are allowed.
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small">Advance your application tracking through the workflow:</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($allowedNext as $next): ?>
                                            <form method="post">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="new_status" value="<?php echo e($next); ?>">
                                                <button type="submit" class="btn <?php echo $next === 'approved' ? 'btn-success' : ($next === 'rejected' ? 'btn-danger' : 'btn-primary-soft'); ?>">
                                                    Mark as <?php echo e(ucwords(str_replace('_', ' ', $next))); ?>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="alert alert-light border small mt-3 mb-0">
                                        <i class="bi bi-info-circle me-1"></i>
                                        "Applied", "Under Review", "Approved" and "Rejected" reflect your own tracking
                                        notes, not the provider's official decision.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">Notes</div>
                            <div class="card-body">
                                <form method="post">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update_notes">
                                    <div class="mb-3">
                                        <textarea class="form-control" name="notes" rows="5"
                                                  placeholder="Record reminders, document checklists, or provider contact details..."><?php echo e($app['notes']); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary-soft btn-sm"><i class="bi bi-save me-1"></i>Save Notes</button>
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
