<?php
/**
 * EduGrant — Scholarship Details (Student)
 *
 * Full scholarship view: requirements, documents, eligibility guide/checker,
 * save, track application, and "Apply Officially" (redirect to provider site).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requireRole('student');

$user = getCurrentUser();
$me   = getStudentProfileByUserId($user['id']);
$pdo  = db();

$id = (int)($_GET['id'] ?? 0);
$scholarship = getStudentVisibleScholarships($id);

if (!$scholarship) {
    http_response_code(404);
    $page_title = 'Not Found';
    $layout = 'dashboard';
    $page_active = 'scholarships';
    include BASE_PATH . '/includes/header.php';
    echo '<div class="container py-5"><div class="card empty-state"><i class="bi bi-search"></i><h5 class="mt-3">Scholarship not found</h5><p>This scholarship may be unavailable, not yet approved, or past its deadline.</p><a class="btn btn-primary-soft mt-2" href="' . url('student/scholarships.php') . '">Back to Directory</a></div></div>';
    include BASE_PATH . '/includes/footer.php';
    exit;
}

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('student/scholarship-details.php?id=' . $id);
    $action = $_POST['action'] ?? '';

    if ($action === 'save' || $action === 'unsave') {
        if ($action === 'save') {
            try {
                $stmt = $pdo->prepare('INSERT INTO saved_scholarships (student_id, scholarship_id) VALUES (?, ?)');
                $stmt->execute([$me['id'], $id]);
                logActivity($user['id'], 'Save scholarship', 'Saved "' . $scholarship['title'] . '".');
                $_SESSION['flash_success'] = 'Scholarship saved to your list.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = 'You have already saved this scholarship.';
            }
        } else {
            $stmt = $pdo->prepare('DELETE FROM saved_scholarships WHERE student_id = ? AND scholarship_id = ?');
            $stmt->execute([$me['id'], $id]);
            logActivity($user['id'], 'Unsave scholarship', 'Removed "' . $scholarship['title'] . '" from saved list.');
            $_SESSION['flash_success'] = 'Scholarship removed from your saved list.';
        }
    } elseif ($action === 'track') {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO applications (student_id, scholarship_id, status, application_date, notes)
                 VALUES (?, ?, "interested", CURDATE(), ?)'
            );
            $stmt->execute([$me['id'], $id, 'Application tracking started.']);
            logActivity($user['id'], 'Application creation', 'Started tracking "' . $scholarship['title'] . '".');
            $_SESSION['flash_success'] = 'Application tracking started. You can update your progress from My Applications.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'You are already tracking this scholarship.';
        }
    }

    redirect('student/scholarship-details.php?id=' . $id);
}

$isSaved = isScholarshipSaved($id, $me['id']);
$tracking = getStudentApplication($id, $me['id']);
$requirements = getScholarshipRequirements($id);
$documents = getRequiredDocuments($id);
$dl = getDeadlineState($scholarship['deadline']);
$eligibility = checkEligibility($me, $scholarship);
$verdict = eligibilityVerdict($eligibility);

$page_title = $scholarship['title'];
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
                        <li class="breadcrumb-item"><a href="<?php echo url('student/scholarships.php'); ?>">Scholarships</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo e($scholarship['title']); ?></li>
                    </ol>
                </nav>

                <div class="card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <span class="badge badge-status bg-<?php echo e($dl['badge']); ?> mb-2"><?php echo e($dl['label']); ?></span>
                                <h3 class="fw-bold text-navy mb-1"><?php echo e($scholarship['title']); ?></h3>
                                <div class="text-muted"><i class="bi bi-building me-1"></i><?php echo e($scholarship['organization_name']); ?></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <form method="post">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="<?php echo $isSaved ? 'unsave' : 'save'; ?>">
                                    <button type="submit" class="btn <?php echo $isSaved ? 'btn-warning' : 'btn-outline-primary'; ?>">
                                        <i class="bi bi-<?php echo $isSaved ? 'bookmark-heart-fill' : 'bookmark'; ?> me-1"></i>
                                        <?php echo $isSaved ? 'Remove from Saved' : 'Save Scholarship'; ?>
                                    </button>
                                </form>
                                <?php if (!$tracking): ?>
                                    <form method="post">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="track">
                                        <button type="submit" class="btn btn-primary-soft">
                                            <i class="bi bi-kanban me-1"></i> Track Application
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a class="btn btn-outline-secondary" href="<?php echo url('student/application-details.php?id=' . $tracking['id']); ?>">
                                        <i class="bi bi-kanban me-1"></i> View My Tracking
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($scholarship['official_url'])): ?>
                                    <a class="btn btn-accent" target="_blank" rel="noopener noreferrer"
                                       href="<?php echo e($scholarship['official_url']); ?>">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Apply Officially
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-mortarboard"></i><strong>Level:</strong> <?php echo e($scholarship['education_level'] ?: 'All'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-geo-alt"></i><strong>Location:</strong> <?php echo e($scholarship['location'] ?: 'N/A'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-cash-coin"></i><strong>Amount:</strong> <?php echo e($scholarship['amount'] ?: 'Varies'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-pie-chart"></i><strong>Type:</strong> <?php echo e($scholarship['scholarship_type'] ?: 'General'); ?></div></div>
                            <div class="col-md-3"><div class="meta-row"><i class="bi bi-alarm"></i><strong>Deadline:</strong> <?php echo e($dl['deadline']); ?> (<?php echo $dl['days_remaining'] >= 0 ? $dl['days_remaining'] . ' days remaining' : 'Closed'; ?>)</div></div>
                        </div>

                        <div class="alert alert-info mt-3 mb-0 small">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Note:</strong> EduGrant does not submit applications to scholarship providers.
                            Students are redirected to the official application website.
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">About This Scholarship</div>
                            <div class="card-body">
                                <p><?php echo nl2br(e($scholarship['description'])); ?></p>
                                <?php if (!empty($scholarship['benefits'])): ?>
                                    <h6 class="fw-bold text-navy mt-3">Benefits</h6>
                                    <p><?php echo nl2br(e($scholarship['benefits'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($scholarship['eligibility_summary'])): ?>
                                    <h6 class="fw-bold text-navy mt-3">Eligibility Summary</h6>
                                    <p><?php echo nl2br(e($scholarship['eligibility_summary'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($scholarship['official_url'])): ?>
                                    <h6 class="fw-bold text-navy mt-3">Official Application Website</h6>
                                    <a href="<?php echo e($scholarship['official_url']); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo e($scholarship['official_url']); ?> <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">Requirements</div>
                            <div class="card-body">
                                <?php if (empty($requirements)): ?>
                                    <p class="text-muted mb-0">No specific requirements listed. Check the official website.</p>
                                <?php else: ?>
                                    <ul class="mb-0">
                                        <?php foreach ($requirements as $req): ?>
                                            <li class="mb-1"><?php echo e($req['requirement']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">Required Documents</div>
                            <div class="card-body">
                                <?php if (empty($documents)): ?>
                                    <p class="text-muted mb-0">No documents listed. Check the official website.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr><th style="width:35%;">Document</th><th>Description</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($documents as $doc): ?>
                                                    <tr>
                                                        <td class="fw-semibold"><i class="bi bi-file-earmark-text me-1 text-primary"></i><?php echo e($doc['document_name']); ?></td>
                                                        <td class="text-muted"><?php echo e($doc['description'] ?: '—'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header"><i class="bi bi-check2-circle me-1"></i>Eligibility Checker</div>
                            <div class="card-body">
                                <div class="alert alert-<?php echo e($verdict['badge']); ?> py-2 mb-3">
                                    <strong><?php echo e($verdict['verdict']); ?></strong>
                                    <div class="small"><?php echo e($verdict['summary']); ?></div>
                                </div>

                                <?php foreach ($eligibility as $item): ?>
                                    <div class="eligibility-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold small"><?php echo e($item['label']); ?></span>
                                            <?php if ($item['status'] === true): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-lg"></i></span>
                                            <?php elseif ($item['status'] === false): ?>
                                                <span class="badge bg-danger"><i class="bi bi-x-lg"></i></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Manual Review</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo e($item['note']); ?></small>
                                    </div>
                                <?php endforeach; ?>

                                <div class="alert alert-light border small mb-0 mt-3">
                                    <i class="bi bi-shield-check me-1"></i>
                                    This eligibility result is only a guide. Final eligibility is determined by the scholarship provider.
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($scholarship['official_url'])): ?>
                            <div class="card">
                                <div class="card-body text-center">
                                    <p class="text-muted small mb-2">Ready to apply?</p>
                                    <a class="btn btn-accent w-100" target="_blank" rel="noopener noreferrer"
                                       href="<?php echo e($scholarship['official_url']); ?>">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Apply Officially
                                    </a>
                                    <div class="text-muted small mt-2">
                                        You will be redirected to the official scholarship application website.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
