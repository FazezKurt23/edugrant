<?php
/**
 * EduGrant — Admin: Pending Scholarship Approvals
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/pending-scholarships.php');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT s.*, p.user_id AS owner FROM scholarships s JOIN provider_profiles p ON p.id = s.provider_id WHERE s.id = ? AND s.status = "pending"');
    $stmt->execute([$id]);
    $scholarship = $stmt->fetch();

    if ($scholarship) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE scholarships SET status = 'approved', rejection_reason = NULL WHERE id = ?")->execute([$id]);
            createNotification((int)$scholarship['owner'], 'Scholarship approved',
                'Your scholarship "' . $scholarship['title'] . '" has been approved and is now visible to students.', 'approval');
            logActivity($user['id'], 'Scholarship approval', 'Approved "' . $scholarship['title'] . '".');
            $_SESSION['flash_success'] = 'Scholarship approved.';
        } elseif ($action === 'reject') {
            $reason = clean($_POST['reason'] ?? 'No reason provided.');
            $pdo->prepare("UPDATE scholarships SET status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $id]);
            createNotification((int)$scholarship['owner'], 'Scholarship rejected',
                'Your scholarship "' . $scholarship['title'] . '" was rejected. Reason: ' . $reason, 'rejection');
            logActivity($user['id'], 'Scholarship rejection', 'Rejected "' . $scholarship['title'] . '".');
            $_SESSION['flash_success'] = 'Scholarship rejected.';
        }
    } else {
        $_SESSION['flash_error'] = 'Scholarship not found or already reviewed.';
    }
    redirect('admin/pending-scholarships.php');
}

$stmt = $pdo->prepare(
    'SELECT s.*, p.organization_name FROM scholarships s
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE s.status = "pending" ORDER BY s.created_at ASC'
);
$stmt->execute();
$pending = $stmt->fetchAll();

$page_title = 'Pending Approvals';
$layout = 'dashboard';
$page_active = 'pending';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-3">Pending Approvals</h4>

                <?php if (empty($pending)): ?>
                    <div class="card empty-state">
                        <i class="bi bi-check2-all"></i>
                        <h5 class="mt-3">All caught up</h5>
                        <p class="mb-0">There are no scholarships waiting for review.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($pending as $s): ?>
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="scholar-title mb-1"><?php echo e($s['title']); ?></h6>
                                                <div class="provider-name mb-2"><i class="bi bi-building me-1"></i><?php echo e($s['organization_name']); ?></div>
                                            </div>
                                            <span class="badge badge-status bg-warning text-dark">Pending</span>
                                        </div>
                                        <p class="text-muted small"><?php echo e(mb_strimwidth($s['description'], 0, 160, '…')); ?></p>
                                        <div class="row g-2 small text-muted">
                                            <div class="col-6"><i class="bi bi-pie-chart me-1"></i><?php echo e($s['scholarship_type'] ?: 'General'); ?></div>
                                            <div class="col-6"><i class="bi bi-geo-alt me-1"></i><?php echo e($s['location'] ?: 'N/A'); ?></div>
                                            <div class="col-6"><i class="bi bi-cash-coin me-1"></i><?php echo e($s['amount'] ?: 'Varies'); ?></div>
                                            <div class="col-6"><i class="bi bi-alarm me-1"></i><?php echo e(formatDate($s['deadline'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-0 pb-3 d-flex justify-content-between flex-wrap gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('admin/view-scholarship.php?id=' . $s['id']); ?>"><i class="bi bi-eye me-1"></i>Review Details</a>
                                        <div class="d-flex gap-2">
                                            <form method="post">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                <button class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Approve</button>
                                            </form>
                                            <form method="post">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                <input type="hidden" name="reason" id="rej-<?php echo $s['id']; ?>" value="">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="promptReject(<?php echo $s['id']; ?>)"><i class="bi bi-x-lg me-1"></i>Reject</button>
                                            </form>
                                        </div>
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

<script>
function promptReject(id) {
    const reason = prompt('Rejection reason (required):');
    if (reason !== null && reason.trim() !== '') {
        document.getElementById('rej-' + id).value = reason.trim();
        document.getElementById('rej-' + id).closest('form').submit();
    } else {
        alert('A rejection reason is required.');
    }
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
