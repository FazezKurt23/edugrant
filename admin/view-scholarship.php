<?php
/**
 * EduGrant — Admin: View Scholarship (read-only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT s.*, p.organization_name, p.contact_person, p.contact_number
     FROM scholarships s JOIN provider_profiles p ON p.id = s.provider_id WHERE s.id = ?'
);
$stmt->execute([$id]);
$scholarship = $stmt->fetch();

if (!$scholarship) {
    http_response_code(404);
    $page_title = 'Not Found';
    $layout = 'dashboard';
    $page_active = 'scholarships';
    include BASE_PATH . '/includes/header.php';
    echo '<div class="container py-5"><div class="card empty-state"><i class="bi bi-exclamation-triangle"></i><h5 class="mt-3">Scholarship not found.</h5><a class="btn btn-primary-soft mt-2" href="' . url('admin/scholarships.php') . '">Back</a></div></div>';
    include BASE_PATH . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/view-scholarship.php?id=' . $id);
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE scholarships SET status = 'approved', rejection_reason = NULL WHERE id = ?")->execute([$id]);
        $pp = $pdo->prepare('SELECT user_id FROM provider_profiles WHERE id = ?');
        $pp->execute([$scholarship['provider_id']]);
        $ownerUserId = (int)$pp->fetchColumn();
        createNotification($ownerUserId, 'Scholarship approved',
            'Your scholarship "' . $scholarship['title'] . '" has been approved and is now visible to students.', 'approval');
        logActivity($user['id'], 'Scholarship approval', 'Approved "' . $scholarship['title'] . '".');
        $_SESSION['flash_success'] = 'Scholarship approved.';
    } elseif ($action === 'reject') {
        $reason = clean($_POST['reason'] ?? 'No reason provided.');
        $pdo->prepare("UPDATE scholarships SET status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $id]);
        $pp = $pdo->prepare('SELECT user_id FROM provider_profiles WHERE id = ?');
        $pp->execute([$scholarship['provider_id']]);
        $ownerUserId = (int)$pp->fetchColumn();
        createNotification($ownerUserId, 'Scholarship rejected',
            'Your scholarship "' . $scholarship['title'] . '" was rejected. Reason: ' . $reason, 'rejection');
        logActivity($user['id'], 'Scholarship rejection', 'Rejected "' . $scholarship['title'] . '".');
        $_SESSION['flash_success'] = 'Scholarship rejected.';
    }
    redirect('admin/view-scholarship.php?id=' . $id);
}

$requirements = getScholarshipRequirements($id);
$documents = getRequiredDocuments($id);
$dl = getDeadlineState($scholarship['deadline']);

$stmt = $pdo->prepare(
    'SELECT a.*, sp.first_name, sp.last_name, sp.course
     FROM applications a JOIN student_profiles sp ON sp.id = a.student_id
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
                        <li class="breadcrumb-item"><a href="<?php echo url('admin/scholarships.php'); ?>">Scholarships</a></li>
                        <li class="breadcrumb-item active"><?php echo e($scholarship['title']); ?></li>
                    </ol>
                </nav>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <span class="badge badge-status bg-<?php echo e(scholarshipStatusBadge($scholarship['status'])); ?> mb-2"><?php echo e(ucfirst($scholarship['status'])); ?></span>
                                <h4 class="fw-bold text-navy mb-1"><?php echo e($scholarship['title']); ?></h4>
                                <div class="text-muted">
                                    <i class="bi bi-building me-1"></i><?php echo e($scholarship['organization_name']); ?>
                                    · <?php echo e($scholarship['contact_person'] ?: ''); ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($scholarship['status'] === 'pending'): ?>
                                    <form method="post">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Approve</button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="reason" id="rej" value="">
                                        <button type="button" class="btn btn-danger" onclick="promptReject()"><i class="bi bi-x-lg me-1"></i>Reject</button>
                                    </form>
                                <?php endif; ?>
                                <a class="btn btn-outline-secondary" href="<?php echo url('admin/scholarships.php'); ?>">Back</a>
                            </div>
                        </div>

                        <?php if ($scholarship['status'] === 'rejected' && $scholarship['rejection_reason']): ?>
                            <div class="alert alert-danger mt-3 mb-0"><i class="bi bi-x-circle me-1"></i><strong>Rejection reason:</strong> <?php echo e($scholarship['rejection_reason']); ?></div>
                        <?php endif; ?>

                        <div class="row g-3 mt-2">
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-mortarboard"></i><strong>Level:</strong> <?php echo e($scholarship['education_level'] ?: 'All'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-geo-alt"></i><strong>Location:</strong> <?php echo e($scholarship['location'] ?: 'N/A'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-cash-coin"></i><strong>Amount:</strong> <?php echo e($scholarship['amount'] ?: 'Varies'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-alarm"></i><strong>Deadline:</strong> <?php echo $scholarship['deadline'] ? e($dl['deadline']) : '—'; ?></div></div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Description &amp; Details</div>
                    <div class="card-body">
                        <p><?php echo nl2br(e($scholarship['description'])); ?></p>
                        <?php if (!empty($scholarship['benefits'])): ?><h6 class="fw-bold text-navy">Benefits</h6><p><?php echo nl2br(e($scholarship['benefits'])); ?></p><?php endif; ?>
                        <?php if (!empty($scholarship['eligibility_summary'])): ?><h6 class="fw-bold text-navy">Eligibility Summary</h6><p><?php echo nl2br(e($scholarship['eligibility_summary'])); ?></p><?php endif; ?>
                        <?php if (!empty($scholarship['official_url'])): ?>
                            <h6 class="fw-bold text-navy">Official URL</h6>
                            <a href="<?php echo e($scholarship['official_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($scholarship['official_url']); ?></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">Requirements (<?php echo count($requirements); ?>)</div>
                            <div class="card-body">
                                <?php if (empty($requirements)): ?><p class="text-muted mb-0">None.</p>
                                <?php else: ?><ul class="mb-0"><?php foreach ($requirements as $r): ?><li class="mb-1"><?php echo e($r['requirement']); ?></li><?php endforeach; ?></ul><?php endif; ?>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">Required Documents (<?php echo count($documents); ?>)</div>
                            <div class="card-body">
                                <?php if (empty($documents)): ?><p class="text-muted mb-0">None.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Document</th><th>Description</th></tr></thead>
                                            <tbody><?php foreach ($documents as $d): ?><tr><td class="fw-semibold"><?php echo e($d['document_name']); ?></td><td class="text-muted"><?php echo e($d['description'] ?: '—'); ?></td></tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">Student Tracking Activity (<?php echo count($apps); ?>)</div>
                            <div class="card-body">
                                <?php if (empty($apps)): ?>
                                    <div class="empty-state"><i class="bi bi-kanban"></i><p class="mb-0 mt-2">No student tracking activity.</p></div>
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

<script>
function promptReject() {
    const reason = prompt('Rejection reason (required):');
    if (reason !== null && reason.trim() !== '') {
        document.getElementById('rej').value = reason.trim();
        document.getElementById('rej').closest('form').submit();
    } else {
        alert('A rejection reason is required.');
    }
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
