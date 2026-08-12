<?php
/**
 * EduGrant — Admin: Provider Management & Verification
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$user = getCurrentUser();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin/providers.php');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    $pp = $pdo->prepare('SELECT pp.*, u.email FROM provider_profiles pp JOIN users u ON u.id = pp.user_id WHERE pp.id = ?');
    $pp->execute([$id]);
    $provider = $pp->fetch();

    if ($provider) {
        if ($action === 'verify') {
            $pdo->prepare("UPDATE provider_profiles SET verification_status = 'verified' WHERE id = ?")->execute([$id]);
            createNotification((int)$provider['user_id'], 'Organization verified',
                'Congratulations! Your organization "' . $provider['organization_name'] . '" has been verified.', 'approval');
            logActivity($user['id'], 'Provider verification', 'Verified provider "' . $provider['organization_name'] . '".');
            $_SESSION['flash_success'] = 'Provider verified.';
        } elseif ($action === 'reject') {
            $reason = clean($_POST['reason'] ?? 'No reason provided.');
            $pdo->prepare("UPDATE provider_profiles SET verification_status = 'rejected' WHERE id = ?")->execute([$id]);
            createNotification((int)$provider['user_id'], 'Organization verification rejected',
                'Your organization "' . $provider['organization_name'] . '" verification was rejected. Reason: ' . $reason, 'rejection');
            logActivity($user['id'], 'Provider verification', 'Rejected verification for "' . $provider['organization_name'] . '".');
            $_SESSION['flash_success'] = 'Provider verification rejected.';
        } elseif ($action === 'set_status') {
            $status = $_POST['status'] ?? '';
            if (in_array($status, ['active', 'inactive', 'suspended'], true)) {
                $pdo->prepare('UPDATE users SET status = ? WHERE id = ? AND role = "provider"')->execute([$status, $provider['user_id']]);
                logActivity($user['id'], 'Provider status update', 'Set provider status to ' . $status . '.');
                $_SESSION['flash_success'] = 'Provider account status updated.';
            }
        }
    } else {
        $_SESSION['flash_error'] = 'Provider not found.';
    }
    redirect('admin/providers.php');
}

$search = clean($_GET['q'] ?? '');
$verifFilter = clean($_GET['verification_status'] ?? '');

$where = ['u.role = "provider"'];
$params = [];
if ($search !== '') {
    $where[] = '(pp.organization_name LIKE ? OR u.email LIKE ? OR pp.contact_person LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($verifFilter, ['pending', 'verified', 'rejected'], true)) {
    $where[] = 'pp.verification_status = ?';
    $params[] = $verifFilter;
}

$stmt = $pdo->prepare(
    'SELECT u.id AS user_id, u.name, u.email, u.status, pp.*
     FROM users u
     JOIN provider_profiles pp ON pp.user_id = u.id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY pp.created_at DESC'
);
$stmt->execute($params);
$providers = $stmt->fetchAll();

$page_title = 'Providers';
$layout = 'dashboard';
$page_active = 'providers';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-3">Providers</h4>

                <div class="card card-body py-3 mb-3">
                    <form method="get" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" placeholder="Search organization, email, contact person..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="verification_status">
                                <option value="">All verification</option>
                                <?php foreach (['pending', 'verified', 'rejected'] as $v): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $verifFilter === $v ? 'selected' : ''; ?>><?php echo ucfirst($v); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary-soft btn-sm"><i class="bi bi-search me-1"></i>Search</button>
                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/providers.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Organization</th>
                                    <th>Contact</th>
                                    <th>Verification</th>
                                    <th>Account</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($providers)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No providers found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($providers as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($p['organization_name']); ?></div>
                                                <small class="text-muted">
                                                    <?php if ($p['website']): ?><a href="<?php echo e($p['website']); ?>" target="_blank" rel="noopener noreferrer">Website</a> · <?php endif; ?>
                                                    <?php echo e($p['address'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo e($p['contact_person'] ?: '—'); ?>
                                                <div class="small text-muted"><?php echo e($p['contact_number'] ?? ''); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $p['verification_status'] === 'verified' ? 'bg-success' : ($p['verification_status'] === 'rejected' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                                    <?php echo e($p['verification_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $p['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo e($p['status']); ?></span>
                                                <div class="small text-muted"><?php echo e($p['email']); ?></div>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1 flex-wrap align-items-center">
                                                    <?php if ($p['verification_status'] !== 'verified'): ?>
                                                        <form method="post">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="verify">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-patch-check me-1"></i>Verify</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($p['verification_status'] !== 'rejected'): ?>
                                                        <form method="post" class="d-inline">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                            <input type="hidden" name="reason" id="reject-reason-<?php echo $p['id']; ?>" value="">
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="promptReject(<?php echo $p['id']; ?>)"><i class="bi bi-x-circle me-1"></i>Reject</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="post" class="d-inline">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="set_status">
                                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                            <option value="active" <?php echo $p['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="inactive" <?php echo $p['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                            <option value="suspended" <?php echo $p['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                        </select>
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
    const reason = prompt('Reason for rejecting this provider verification:');
    if (reason !== null) {
        document.getElementById('reject-reason-' + id).value = reason;
        document.getElementById('reject-reason-' + id).closest('form').submit();
    }
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
