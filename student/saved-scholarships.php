<?php
/**
 * EduGrant — Saved Scholarships (Student)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('student');

$user = getCurrentUser();
$me   = getStudentProfileByUserId($user['id']);
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('student/saved-scholarships.php');
    $id = (int)($_POST['scholarship_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'unsave') {
        $stmt = $pdo->prepare('DELETE FROM saved_scholarships WHERE student_id = ? AND scholarship_id = ?');
        $stmt->execute([$me['id'], $id]);
        logActivity($user['id'], 'Unsave scholarship', 'Removed scholarship #' . $id . ' from saved list.');
        $_SESSION['flash_success'] = 'Scholarship removed from your saved list.';
    }
    redirect('student/saved-scholarships.php');
}

$stmt = $pdo->prepare(
    'SELECT s.*, p.organization_name
     FROM saved_scholarships ss
     JOIN scholarships s ON s.id = ss.scholarship_id
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE ss.student_id = ?
     ORDER BY ss.created_at DESC'
);
$stmt->execute([$me['id']]);
$saved = $stmt->fetchAll();

$page_title = 'Saved Scholarships';
$layout = 'dashboard';
$page_active = 'saved';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h4 class="fw-bold text-navy mb-0">Saved Scholarships</h4>
                        <p class="text-muted mb-0"><?php echo count($saved); ?> scholarship(s) in your list.</p>
                    </div>
                    <a class="btn btn-primary-soft" href="<?php echo url('student/scholarships.php'); ?>"><i class="bi bi-plus-circle me-1"></i>Browse More</a>
                </div>

                <?php if (empty($saved)): ?>
                    <div class="card empty-state">
                        <i class="bi bi-bookmark-heart"></i>
                        <h5 class="mt-3">No saved scholarships yet</h5>
                        <p>Click the bookmark button on any scholarship to save it here.</p>
                        <div><a class="btn btn-primary-soft mt-2" href="<?php echo url('student/scholarships.php'); ?>">Find Scholarships</a></div>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($saved as $s): $dl = getDeadlineState($s['deadline']); $visible = ($s['status'] === 'approved' && !$dl['expired']); ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card scholar-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="badge badge-status bg-<?php echo e($visible ? $dl['badge'] : scholarshipStatusBadge($s['status'])); ?>">
                                                <?php echo e($visible ? $dl['label'] : ucfirst($s['status'])); ?>
                                            </span>
                                            <form method="post">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="scholarship_id" value="<?php echo $s['id']; ?>">
                                                <input type="hidden" name="action" value="unsave">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove from saved">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <h6 class="scholar-title mt-2 mb-1"><?php echo e($s['title']); ?></h6>
                                        <div class="provider-name mb-2"><i class="bi bi-building me-1"></i><?php echo e($s['organization_name']); ?></div>
                                        <div class="meta-row mb-1"><i class="bi bi-geo-alt"></i><?php echo e($s['location'] ?: 'N/A'); ?></div>
                                        <div class="deadline-chip mt-2"><i class="bi bi-alarm"></i>
                                            <?php if ($s['deadline']): ?>
                                                <?php echo e($dl['deadline']); ?>
                                                <?php if ($dl['days_remaining'] >= 0): ?><strong>(<?php echo $dl['days_remaining']; ?> days)</strong><?php endif; ?>
                                            <?php else: ?>No deadline<?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-0 pb-3 pt-0">
                                        <a class="btn btn-primary-soft btn-sm w-100" href="<?php echo url('student/scholarship-details.php?id=' . $s['id']); ?>">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
