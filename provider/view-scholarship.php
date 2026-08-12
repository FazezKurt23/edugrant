<?php
/**
 * EduGrant — Provider: View Scholarship
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('provider');

$user = getCurrentUser();
$me   = getProviderProfileByUserId($user['id']);
$pdo  = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM scholarships WHERE id = ? AND provider_id = ?');
$stmt->execute([$id, $me['id']]);
$scholarship = $stmt->fetch();

if (!$scholarship) {
    http_response_code(404);
    $page_title = 'Not Found';
    $layout = 'dashboard';
    $page_active = 'scholarships';
    include BASE_PATH . '/includes/header.php';
    echo '<div class="container py-5"><div class="card empty-state"><i class="bi bi-exclamation-triangle"></i><h5 class="mt-3">Scholarship not found or you do not have permission to view it.</h5><a class="btn btn-primary-soft mt-2" href="' . url('provider/scholarships.php') . '">Back to My Scholarships</a></div></div>';
    include BASE_PATH . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    requireCsrf('provider/view-scholarship.php?id=' . $id);
    if ($scholarship['status'] === 'draft' || $scholarship['status'] === 'rejected') {
        $pdo->prepare("UPDATE scholarships SET status = 'pending', rejection_reason = NULL WHERE id = ? AND provider_id = ?")
            ->execute([$id, $me['id']]);
        logActivity($user['id'], 'Scholarship submission', 'Submitted "' . $scholarship['title'] . '" for approval.');
        $_SESSION['flash_success'] = 'Scholarship submitted for administrator approval.';
    } else {
        $_SESSION['flash_error'] = 'Only draft or rejected scholarships can be submitted.';
    }
    redirect('provider/view-scholarship.php?id=' . $id);
}

$requirements = getScholarshipRequirements($id);
$documents = getRequiredDocuments($id);
$dl = getDeadlineState($scholarship['deadline']);

$stmt = $pdo->prepare(
    'SELECT a.*, sp.first_name, sp.last_name, sp.course, sp.school
     FROM applications a
     JOIN student_profiles sp ON sp.id = a.student_id
     WHERE a.scholarship_id = ? ORDER BY a.updated_at DESC'
);
$stmt->execute([$id]);
$apps = $stmt->fetchAll();

$page_title = 'View Scholarship';
$layout = 'dashboard';
$page_active = 'scholarships';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo url('provider/scholarships.php'); ?>">My Scholarships</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo e($scholarship['title']); ?></li>
                    </ol>
                </nav>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <span class="badge badge-status bg-<?php echo e(scholarshipStatusBadge($scholarship['status'])); ?> mb-2"><?php echo e(ucfirst($scholarship['status'])); ?></span>
                                <h4 class="fw-bold text-navy mb-1"><?php echo e($scholarship['title']); ?></h4>
                                <div class="text-muted"><?php echo e($me['organization_name']); ?> · Created <?php echo e(formatDate($scholarship['created_at'], 'M j, Y')); ?></div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php if ($scholarship['status'] === 'draft' || $scholarship['status'] === 'rejected'): ?>
                                    <form method="post">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="submit">
                                        <button type="submit" class="btn btn-primary-soft"><i class="bi bi-send me-1"></i>Submit for Approval</button>
                                    </form>
                                <?php endif; ?>
                                <a class="btn btn-outline-secondary" href="<?php echo url('provider/edit-scholarship.php?id=' . $id); ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
                            </div>
                        </div>

                        <?php if ($scholarship['status'] === 'rejected' && $scholarship['rejection_reason']): ?>
                            <div class="alert alert-danger mt-3 mb-0">
                                <i class="bi bi-x-circle me-1"></i><strong>Rejected.</strong>
                                Reason: <?php echo e($scholarship['rejection_reason']); ?>
                                <div class="small mt-1">Edit the scholarship to address the issue, then resubmit for approval.</div>
                            </div>
                        <?php elseif ($scholarship['status'] === 'pending'): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="bi bi-hourglass-split me-1"></i>This scholarship is <strong>pending review</strong> by an administrator.
                            </div>
                        <?php elseif ($scholarship['status'] === 'approved'): ?>
                            <div class="alert alert-success mt-3 mb-0">
                                <i class="bi bi-check-circle me-1"></i>This scholarship is <strong>approved</strong> and visible to students.
                            </div>
                        <?php endif; ?>

                        <div class="row g-3 mt-2">
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-mortarboard"></i><strong>Level:</strong> <?php echo e($scholarship['education_level'] ?: 'All'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-geo-alt"></i><strong>Location:</strong> <?php echo e($scholarship['location'] ?: 'N/A'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-cash-coin"></i><strong>Amount:</strong> <?php echo e($scholarship['amount'] ?: 'Varies'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-pie-chart"></i><strong>Type:</strong> <?php echo e($scholarship['scholarship_type'] ?: 'General'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-alarm"></i><strong>Deadline:</strong> <?php echo $scholarship['deadline'] ? e($dl['deadline']) : '—'; ?></div></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card mb-4">
                            <div class="card-header">Description</div>
                            <div class="card-body">
                                <p><?php echo nl2br(e($scholarship['description'])); ?></p>
                                <?php if (!empty($scholarship['benefits'])): ?>
                                    <h6 class="fw-bold text-navy">Benefits</h6>
                                    <p><?php echo nl2br(e($scholarship['benefits'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($scholarship['eligibility_summary'])): ?>
                                    <h6 class="fw-bold text-navy">Eligibility Summary</h6>
                                    <p><?php echo nl2br(e($scholarship['eligibility_summary'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($scholarship['official_url'])): ?>
                                    <h6 class="fw-bold text-navy">Official URL</h6>
                                    <a href="<?php echo e($scholarship['official_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($scholarship['official_url']); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">Requirements (<?php echo count($requirements); ?>)</div>
                            <div class="card-body">
                                <?php if (empty($requirements)): ?>
                                    <p class="text-muted mb-0">No requirements listed.</p>
                                <?php else: ?>
                                    <ul class="mb-0"><?php foreach ($requirements as $r): ?><li class="mb-1"><?php echo e($r['requirement']); ?></li><?php endforeach; ?></ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">Required Documents (<?php echo count($documents); ?>)</div>
                            <div class="card-body">
                                <?php if (empty($documents)): ?>
                                    <p class="text-muted mb-0">No documents listed.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Document</th><th>Description</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($documents as $d): ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?php echo e($d['document_name']); ?></td>
                                                        <td class="text-muted"><?php echo e($d['description'] ?: '—'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-header">Student Tracking Activity (<?php echo count($apps); ?>)</div>
                            <div class="card-body">
                                <?php if (empty($apps)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-kanban"></i>
                                        <p class="mb-0 mt-2">No students are tracking this scholarship yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Student</th><th>Status</th><th>Updated</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($apps as $a): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold small"><?php echo e($a['first_name'] . ' ' . $a['last_name']); ?></div>
                                                            <div class="small text-muted"><?php echo e($a['course'] ?: ''); ?></div>
                                                        </td>
                                                        <td><span class="badge badge-status bg-<?php echo e(appStatusBadge($a['status'])); ?>"><?php echo e(ucwords(str_replace('_', ' ', $a['status']))); ?></span></td>
                                                        <td class="small text-muted"><?php echo e(formatDate($a['updated_at'], 'M j, Y')); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
