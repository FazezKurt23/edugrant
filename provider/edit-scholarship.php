<?php
/**
 * EduGrant — Provider: Edit Scholarship
 *
 * Only the owning provider may edit. Editing and resubmitting a
 * rejected/approved scholarship returns it to "pending".
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
    echo '<div class="container py-5"><div class="card empty-state"><i class="bi bi-exclamation-triangle"></i><h5 class="mt-3">Scholarship not found or you do not have permission to edit it.</h5><a class="btn btn-primary-soft mt-2" href="' . url('provider/scholarships.php') . '">Back to My Scholarships</a></div></div>';
    include BASE_PATH . '/includes/footer.php';
    exit;
}

$requirements = getScholarshipRequirements($id);
$documents = getRequiredDocuments($id);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('provider/edit-scholarship.php?id=' . $id);

    $title       = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $type        = clean($_POST['scholarship_type'] ?? '');
    $eduLevel    = clean($_POST['education_level'] ?? '');
    $location    = clean($_POST['location'] ?? '');
    $amount      = clean($_POST['amount'] ?? '');
    $benefits    = clean($_POST['benefits'] ?? '');
    $eligibility = clean($_POST['eligibility_summary'] ?? '');
    $deadline    = clean($_POST['deadline'] ?? '');
    $officialUrl = clean($_POST['official_url'] ?? '');
    $submit      = ($_POST['submit_action'] ?? 'draft') === 'submit';

    $requirementsNew = array_values(array_filter(array_map('clean', $_POST['requirements'] ?? [])));
    $docNames     = array_values(array_filter(array_map('clean', $_POST['doc_name'] ?? [])));
    $docDescs     = array_map('clean', $_POST['doc_description'] ?? []);

    if ($title === '' || $description === '') {
        $errors[] = 'Title and description are required.';
    }
    if ($deadline !== '' && !strtotime($deadline)) {
        $errors[] = 'Deadline date is invalid.';
    }
    if ($officialUrl !== '' && !validateUrl($officialUrl)) {
        $errors[] = 'Official URL is not a valid web address (http/https only).';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Determine new status
            if ($submit) {
                $newStatus = 'pending';
            } elseif (in_array($scholarship['status'], ['approved', 'expired', 'rejected'], true)) {
                $newStatus = 'pending'; // editing requires resubmission
            } else {
                $newStatus = 'draft';
            }

            $stmt = $pdo->prepare(
                'UPDATE scholarships SET
                    title = ?, description = ?, scholarship_type = ?, education_level = ?,
                    location = ?, amount = ?, benefits = ?, eligibility_summary = ?,
                    deadline = ?, official_url = ?, status = ?, rejection_reason = NULL
                 WHERE id = ? AND provider_id = ?'
            );
            $stmt->execute([
                $title, $description, $type, $eduLevel,
                $location, $amount, $benefits, $eligibility,
                $deadline !== '' ? $deadline : null, $officialUrl !== '' ? $officialUrl : null,
                $newStatus, $id, $me['id'],
            ]);

            // Replace requirements & documents
            $pdo->prepare('DELETE FROM scholarship_requirements WHERE scholarship_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM required_documents WHERE scholarship_id = ?')->execute([$id]);

            $reqStmt = $pdo->prepare('INSERT INTO scholarship_requirements (scholarship_id, requirement) VALUES (?, ?)');
            foreach ($requirementsNew as $req) {
                $reqStmt->execute([$id, $req]);
            }
            $docStmt = $pdo->prepare('INSERT INTO required_documents (scholarship_id, document_name, description) VALUES (?, ?, ?)');
            foreach ($docNames as $i => $doc) {
                $docStmt->execute([$id, $doc, $docDescs[$i] ?? '']);
            }

            logActivity($user['id'], 'Scholarship update', 'Updated scholarship "' . $title . '".');
            if ($submit) {
                logActivity($user['id'], 'Scholarship submission', 'Resubmitted "' . $title . '" for approval.');
            }

            $pdo->commit();

            $_SESSION['flash_success'] = $submit
                ? 'Scholarship updated and resubmitted for approval.'
                : 'Scholarship updated.';
            redirect('provider/view-scholarship.php?id=' . $id);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Edit scholarship error: ' . $e->getMessage());
            $errors[] = 'Could not save changes. Please try again.';
        }
    }
}

$page_title = 'Edit Scholarship';
$layout = 'dashboard';
$page_active = 'scholarships';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-1">Edit Scholarship</h4>
                <p class="text-muted">Update the details below. Editing an approved or rejected scholarship requires resubmission.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $er): ?><li><?php echo e($er); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <div class="alert alert-light border small">
                    Current status: <span class="badge badge-status bg-<?php echo e(scholarshipStatusBadge($scholarship['status'])); ?>"><?php echo e(ucfirst($scholarship['status'])); ?></span>
                    <?php if ($scholarship['status'] === 'rejected' && $scholarship['rejection_reason']): ?>
                        <br><strong>Rejection reason:</strong> <?php echo e($scholarship['rejection_reason']); ?>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?php echo url('provider/edit-scholarship.php?id=' . $id); ?>">
                    <?php echo csrfField(); ?>

                    <div class="card mb-4">
                        <div class="card-header"><i class="bi bi-pencil-square me-1"></i>Scholarship Details</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label required">Title</label>
                                    <input type="text" class="form-control" name="title" value="<?php echo e($_POST['title'] ?? $scholarship['title']); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label required">Description</label>
                                    <textarea class="form-control" name="description" rows="4" required><?php echo e($_POST['description'] ?? $scholarship['description']); ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Scholarship type</label>
                                    <select class="form-select" name="scholarship_type">
                                        <option value="">Select type</option>
                                        <?php foreach (['Merit-Based', 'Need-Based', 'Leadership', 'Diversity', 'Skill-Based', 'Research Grant', 'Other'] as $t): ?>
                                            <option <?php echo ($_POST['scholarship_type'] ?? $scholarship['scholarship_type']) === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Education level</label>
                                    <select class="form-select" name="education_level">
                                        <option value="">All levels</option>
                                        <?php foreach (['High School', 'Undergraduate', 'College', 'Postgraduate', 'Vocational'] as $l): ?>
                                            <option <?php echo ($_POST['education_level'] ?? $scholarship['education_level']) === $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" value="<?php echo e($_POST['location'] ?? $scholarship['location']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Amount</label>
                                    <input type="text" class="form-control" name="amount" value="<?php echo e($_POST['amount'] ?? $scholarship['amount']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Deadline</label>
                                    <input type="date" class="form-control" name="deadline" value="<?php echo e($_POST['deadline'] ?? $scholarship['deadline']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Official URL</label>
                                    <input type="url" class="form-control" name="official_url" value="<?php echo e($_POST['official_url'] ?? $scholarship['official_url']); ?>" placeholder="https://...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Benefits</label>
                                    <textarea class="form-control" name="benefits" rows="3"><?php echo e($_POST['benefits'] ?? $scholarship['benefits']); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Eligibility summary</label>
                                    <textarea class="form-control" name="eligibility_summary" rows="3"><?php echo e($_POST['eligibility_summary'] ?? $scholarship['eligibility_summary']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-check2-square me-1"></i>Requirements</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addReq()"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                        <div class="card-body">
                            <div id="req-rows">
                                <?php foreach ($requirements as $r): ?>
                                    <div class="row g-2 req-row mb-2">
                                        <div class="col-11"><input type="text" class="form-control" name="requirements[]" value="<?php echo e($r['requirement']); ?>"></div>
                                        <div class="col-1"><button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($requirements)): ?>
                                    <div class="row g-2 req-row mb-2">
                                        <div class="col-11"><input type="text" class="form-control" name="requirements[]" placeholder="e.g. Must be an undergraduate student"></div>
                                        <div class="col-1"><button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-text me-1"></i>Required Documents</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDoc()"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                        <div class="card-body">
                            <div id="doc-rows">
                                <?php foreach ($documents as $d): ?>
                                    <div class="row g-2 doc-row mb-2">
                                        <div class="col-md-4"><input type="text" class="form-control" name="doc_name[]" value="<?php echo e($d['document_name']); ?>"></div>
                                        <div class="col-md-7"><input type="text" class="form-control" name="doc_description[]" value="<?php echo e($d['description']); ?>"></div>
                                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($documents)): ?>
                                    <div class="row g-2 doc-row mb-2">
                                        <div class="col-md-4"><input type="text" class="form-control" name="doc_name[]" placeholder="Document name"></div>
                                        <div class="col-md-7"><input type="text" class="form-control" name="doc_description[]" placeholder="Description (optional)"></div>
                                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mb-4">
                        <a class="btn btn-outline-secondary" href="<?php echo url('provider/view-scholarship.php?id=' . $id); ?>">Cancel</a>
                        <?php if (in_array($scholarship['status'], ['approved', 'expired', 'rejected', 'pending'], true)): ?>
                            <button type="submit" name="submit_action" value="submit" class="btn btn-primary-soft">
                                <i class="bi bi-send me-1"></i>Save &amp; Resubmit for Approval
                            </button>
                        <?php else: ?>
                            <button type="submit" name="submit_action" value="draft" class="btn btn-secondary"><i class="bi bi-save me-1"></i>Save Draft</button>
                            <button type="submit" name="submit_action" value="submit" class="btn btn-primary-soft"><i class="bi bi-send me-1"></i>Submit for Approval</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function addReq() {
    const container = document.getElementById('req-rows');
    const rows = container.querySelectorAll('.req-row');
    const clone = rows[rows.length - 1].cloneNode(true);
    clone.querySelectorAll('input').forEach(input => input.value = '');
    container.appendChild(clone);
    bindRemove();
}
function addDoc() {
    const container = document.getElementById('doc-rows');
    const rows = container.querySelectorAll('.doc-row');
    const clone = rows[rows.length - 1].cloneNode(true);
    clone.querySelectorAll('input').forEach(input => input.value = '');
    container.appendChild(clone);
    bindRemove();
}
function bindRemove() {
    document.querySelectorAll('.remove-row').forEach(btn => {
        btn.onclick = function () {
            const container = btn.closest('div[id$="-rows"]');
            if (container.querySelectorAll('.row').length > 1) {
                btn.closest('.row').remove();
            }
        };
    });
}
document.addEventListener('DOMContentLoaded', bindRemove);
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
