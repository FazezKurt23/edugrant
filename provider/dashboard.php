<?php
/**
 * EduGrant — Provider Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('provider');

$user = getCurrentUser();
$me   = getProviderProfileByUserId($user['id']);
$pdo  = db();

// ---- Statistics ----
$stmt = $pdo->prepare('SELECT COUNT(*) FROM scholarships WHERE provider_id = ?');
$stmt->execute([$me['id']]);
$total = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM scholarships WHERE provider_id = ? AND status = 'approved'");
$stmt->execute([$me['id']]);
$approved = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM scholarships WHERE provider_id = ? AND status = 'pending'");
$stmt->execute([$me['id']]);
$pending = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM scholarships WHERE provider_id = ? AND status = 'rejected'");
$stmt->execute([$me['id']]);
$rejected = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM applications a
     JOIN scholarships s ON s.id = a.scholarship_id
     WHERE s.provider_id = ?'
);
$stmt->execute([$me['id']]);
$trackedApps = (int)$stmt->fetchColumn();

// Recent scholarships
$stmt = $pdo->prepare('SELECT * FROM scholarships WHERE provider_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$me['id']]);
$recent = $stmt->fetchAll();

// Recent tracked applications
$stmt = $pdo->prepare(
    'SELECT a.*, s.title, sp.first_name, sp.last_name
     FROM applications a
     JOIN scholarships s ON s.id = a.scholarship_id
     JOIN student_profiles sp ON sp.id = a.student_id
     WHERE s.provider_id = ?
     ORDER BY a.updated_at DESC LIMIT 5'
);
$stmt->execute([$me['id']]);
$recentApps = $stmt->fetchAll();

$page_title = 'Provider Dashboard';
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
                        <h4 class="fw-bold text-navy mb-0">Welcome, <?php echo e($me['organization_name']); ?></h4>
                        <p class="text-muted mb-0">Manage your scholarship listings and monitor student tracking activity.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php $verifBadge = ['pending' => 'warning text-dark', 'verified' => 'success', 'rejected' => 'danger']; ?>
                        <span class="badge badge-status bg-<?php echo e($verifBadge[$me['verification_status']] ?? 'secondary'); ?>">
                            <i class="bi bi-patch-check me-1"></i><?php echo ucfirst($me['verification_status']); ?>
                        </span>
                        <a class="btn btn-primary-soft" href="<?php echo url('provider/add-scholarship.php'); ?>"><i class="bi bi-plus-circle me-1"></i>Add Scholarship</a>
                    </div>
                </div>

                <?php if ($me['verification_status'] === 'pending'): ?>
                    <div class="alert alert-warning d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-hourglass-split"></i>
                        <div>Your organization is pending verification. Scholarships will become visible to students after an administrator approves your listing.</div>
                    </div>
                <?php elseif ($me['verification_status'] === 'rejected'): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-x-circle"></i>
                        <div>Your organization verification was rejected. Please update your profile or contact the administrator.</div>
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-blue-soft"><i class="bi bi-collection text-primary"></i></div>
                                <div><div class="stat-value" data-count="<?php echo $total; ?>"><?php echo $total; ?></div><div class="stat-label">Total Scholarships</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-green-soft"><i class="bi bi-check-circle text-success"></i></div>
                                <div><div class="stat-value" data-count="<?php echo $approved; ?>"><?php echo $approved; ?></div><div class="stat-label">Approved</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-orange-soft"><i class="bi bi-hourglass-split text-warning"></i></div>
                                <div><div class="stat-value" data-count="<?php echo $pending; ?>"><?php echo $pending; ?></div><div class="stat-label">Pending</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card stat-card p-3 h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-red-soft"><i class="bi bi-x-circle text-danger"></i></div>
                                <div><div class="stat-value" data-count="<?php echo $rejected; ?>"><?php echo $rejected; ?></div><div class="stat-label">Rejected</div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recent Scholarships</span>
                                <a class="small text-decoration-none" href="<?php echo url('provider/scholarships.php'); ?>">View all</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead><tr><th>Title</th><th>Status</th><th>Deadline</th><th></th></tr></thead>
                                        <tbody>
                                            <?php if (empty($recent)): ?>
                                                <tr><td colspan="4" class="text-center text-muted py-4">No scholarships yet.
                                                    <a class="d-block small" href="<?php echo url('provider/add-scholarship.php'); ?>">Create your first scholarship</a></td></tr>
                                            <?php else: ?>
                                                <?php foreach ($recent as $s): ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?php echo e($s['title']); ?></td>
                                                        <td><span class="badge badge-status bg-<?php echo e(scholarshipStatusBadge($s['status'])); ?>"><?php echo e(ucfirst($s['status'])); ?></span></td>
                                                        <td><?php echo e(formatDate($s['deadline'])); ?></td>
                                                        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo url('provider/view-scholarship.php?id=' . $s['id']); ?>">View</a></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Tracked Applications</span>
                                <a class="small text-decoration-none" href="<?php echo url('provider/applications.php'); ?>">View all</a>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <span class="badge badge-status bg-primary"><?php echo $trackedApps; ?> total tracked</span>
                                </div>
                                <?php if (empty($recentApps)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-kanban"></i>
                                        <p class="mb-0 mt-2">No student tracking activity yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentApps as $a): ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <div class="fw-semibold small"><?php echo e($a['first_name'] . ' ' . $a['last_name']); ?></div>
                                                <div class="small text-muted"><?php echo e($a['title']); ?></div>
                                            </div>
                                            <span class="badge badge-status bg-<?php echo e(appStatusBadge($a['status'])); ?>"><?php echo e(ucwords(str_replace('_', ' ', $a['status']))); ?></span>
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
