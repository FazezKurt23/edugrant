<?php
/**
 * EduGrant — Provider: My Scholarships
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('provider');

$user = getCurrentUser();
$me   = getProviderProfileByUserId($user['id']);
$pdo  = db();

// Delete scholarship (provider owns it, only drafts/rejected may be deleted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    requireCsrf('provider/scholarships.php');
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM scholarships WHERE id = ? AND provider_id = ?');
    $stmt->execute([$id, $me['id']]);
    $scholarship = $stmt->fetch();
    if ($scholarship) {
        $pdo->prepare('DELETE FROM scholarships WHERE id = ?')->execute([$id]);
        logActivity($user['id'], 'Scholarship deletion', 'Deleted "' . $scholarship['title'] . '".');
        $_SESSION['flash_success'] = 'Scholarship deleted.';
    } else {
        $_SESSION['flash_error'] = 'Scholarship not found or you do not own it.';
    }
    redirect('provider/scholarships.php');
}

$statusFilter = clean($_GET['status'] ?? '');
$where = 's.provider_id = ?';
$params = [$me['id']];
if (in_array($statusFilter, ['draft', 'pending', 'approved', 'rejected', 'expired'], true)) {
    $where .= ' AND s.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM applications a WHERE a.scholarship_id = s.id) AS app_count
     FROM scholarships s WHERE $where ORDER BY s.created_at DESC");
$stmt->execute($params);
$scholarships = $stmt->fetchAll();

$page_title = 'My Scholarships';
$layout = 'dashboard';
$page_active = 'scholarships';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="fw-bold text-navy mb-0">My Scholarships</h4>
                        <p class="text-muted mb-0">Create, edit, and submit scholarships for administrator approval.</p>
                    </div>
                    <a class="btn btn-primary-soft" href="<?php echo url('provider/add-scholarship.php'); ?>"><i class="bi bi-plus-circle me-1"></i>Add Scholarship</a>
                </div>

                <div class="card card-body py-2 mb-3">
                    <form method="get" class="row g-2 align-items-center">
                        <div class="col-8 col-md-4 col-lg-3">
                            <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                <?php foreach (['draft', 'pending', 'approved', 'rejected', 'expired'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('provider/scholarships.php'); ?>">Reset</a>
                        </div>
                    </form>
                </div>

                <?php if (empty($scholarships)): ?>
                    <div class="card empty-state">
                        <i class="bi bi-collection"></i>
                        <h5 class="mt-3">No scholarships found</h5>
                        <p class="mb-0">Create your first scholarship listing to begin.</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Deadline</th>
                                        <th>Tracked</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scholarships as $s): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($s['title']); ?></div>
                                                <small class="text-muted"><?php echo e($s['scholarship_type'] ?: 'General'); ?> · <?php echo e($s['location'] ?: 'N/A'); ?></small>
                                            </td>
                                            <td><span class="badge badge-status bg-<?php echo e(scholarshipStatusBadge($s['status'])); ?>"><?php echo e(ucfirst($s['status'])); ?></span></td>
                                            <td><?php echo e(formatDate($s['deadline'])); ?></td>
                                            <td><?php echo $s['app_count']; ?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo url('provider/view-scholarship.php?id=' . $s['id']); ?>">View</a>
                                                <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('provider/edit-scholarship.php?id=' . $s['id']); ?>">Edit</a>
                                                <?php if (in_array($s['status'], ['draft', 'rejected'], true)): ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this scholarship? This cannot be undone.');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
