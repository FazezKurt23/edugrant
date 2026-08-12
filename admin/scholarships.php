<?php
/**
 * EduGrant — Admin: Scholarship Management (all scholarships)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/scholarships.php');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT s.*, p.user_id AS owner_user_id FROM scholarships s JOIN provider_profiles p ON p.id = s.provider_id WHERE s.id = ?');
    $stmt->execute([$id]);
    $scholarship = $stmt->fetch();

    if ($scholarship) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE scholarships SET status = 'approved', rejection_reason = NULL WHERE id = ?")->execute([$id]);
            createNotification((int)$scholarship['owner_user_id'], 'Scholarship approved',
                'Your scholarship "' . $scholarship['title'] . '" has been approved and is now visible to students.', 'approval');
            logActivity($user['id'], 'Scholarship approval', 'Approved "' . $scholarship['title'] . '".');
            $_SESSION['flash_success'] = 'Scholarship approved.';
        } elseif ($action === 'reject') {
            $reason = clean($_POST['reason'] ?? 'No reason provided.');
            $pdo->prepare("UPDATE scholarships SET status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $id]);
            createNotification((int)$scholarship['owner_user_id'], 'Scholarship rejected',
                'Your scholarship "' . $scholarship['title'] . '" was rejected. Reason: ' . $reason, 'rejection');
            logActivity($user['id'], 'Scholarship rejection', 'Rejected "' . $scholarship['title'] . '". Reason: ' . $reason);
            $_SESSION['flash_success'] = 'Scholarship rejected.';
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM scholarships WHERE id = ?')->execute([$id]);
            logActivity($user['id'], 'Scholarship deletion', 'Deleted "' . $scholarship['title'] . '".');
            $_SESSION['flash_success'] = 'Scholarship deleted.';
        }
    } else {
        $_SESSION['flash_error'] = 'Scholarship not found.';
    }
    redirect('admin/scholarships.php');
}

$search = clean($_GET['q'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(s.title LIKE ? OR p.organization_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like);
}
if (in_array($statusFilter, ['draft', 'pending', 'approved', 'rejected', 'expired'], true)) {
    $where[] = 's.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare(
    'SELECT s.*, p.organization_name,
            (SELECT COUNT(*) FROM applications a WHERE a.scholarship_id = s.id) AS app_count
     FROM scholarships s
     JOIN provider_profiles p ON p.id = s.provider_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY s.created_at DESC'
);
$stmt->execute($params);
$scholarships = $stmt->fetchAll();

$page_title = 'Scholarship Management';
$layout = 'dashboard';
$page_active = 'scholarships';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-3">Scholarships</h4>

                <div class="card card-body py-3 mb-3">
                    <form method="get" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" placeholder="Search title or provider..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All statuses</option>
                                <?php foreach (['draft', 'pending', 'approved', 'rejected', 'expired'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary-soft btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/scholarships.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Scholarship</th>
                                    <th>Provider</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                    <th>Tracked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($scholarships)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No scholarships found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($scholarships as $s): $dl = getDeadlineState($s['deadline']); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($s['title']); ?></div>
                                                <small class="text-muted"><?php echo e($s['scholarship_type'] ?: 'General'); ?></small>
                                            </td>
                                            <td><?php echo e($s['organization_name']); ?></td>
                                            <td><span class="badge badge-status bg-<?php echo e(scholarshipStatusBadge($s['status'])); ?>"><?php echo e(ucfirst($s['status'])); ?></span></td>
                                            <td>
                                                <?php echo $s['deadline'] ? e($dl['deadline']) : '—'; ?>
                                                <div class="small text-muted"><?php echo e($dl['label']); ?></div>
                                            </td>
                                            <td><?php echo $s['app_count']; ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1 flex-wrap align-items-center">
                                                    <?php if ($s['status'] === 'pending'): ?>
                                                        <form method="post">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-lg"></i> Approve</button>
                                                        </form>
                                                        <form method="post" class="d-inline">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                            <input type="hidden" name="reason" id="rej-<?php echo $s['id']; ?>" value="">
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="promptReject(<?php echo $s['id']; ?>)">Reject</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('admin/view-scholarship.php?id=' . $s['id']); ?>">View</a>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this scholarship and all its data? This cannot be undone.');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </div>
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
