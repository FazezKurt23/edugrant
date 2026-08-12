<?php
/**
 * EduGrant — Provider: Add Scholarship
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('provider');

$user = getCurrentUser();
$me   = getProviderProfileByUserId($user['id']);
$pdo  = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('provider/add-scholarship.php');

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

    $requirements = array_values(array_filter(array_map('clean', $_POST['requirements'] ?? [])));
    $docNames     = array_values(array_filter(array_map('clean', $_POST['doc_name'] ?? [])));
    $docDescs     = array_map('clean', $_POST['doc_description'] ?? []);

    // Validation
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
            $status = $submit ? 'pending' : 'draft';

            $stmt = $pdo->prepare(
                'INSERT INTO scholarships
                 (provider_id, title, description, scholarship_type, education_level, location,
                  amount, benefits, eligibility_summary, deadline, official_url, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $me['id'], $title, $description, $type, $eduLevel, $location,
                $amount, $benefits, $eligibility, $deadline !== '' ? $deadline : null,
                $officialUrl !== '' ? $officialUrl : null, $status,
            ]);
            $scholarshipId = (int)$pdo->lastInsertId();

            $reqStmt = $pdo->prepare('INSERT INTO scholarship_requirements (scholarship_id, requirement) VALUES (?, ?)');
            foreach ($requirements as $req) {
                $reqStmt->execute([$scholarshipId, $req]);
            }

            $docStmt = $pdo->prepare('INSERT INTO required_documents (scholarship_id, document_name, description) VALUES (?, ?, ?)');
            foreach ($docNames as $i => $doc) {
                $docStmt->execute([$scholarshipId, $doc, $docDescs[$i] ?? '']);
            }

            logActivity($user['id'], 'Scholarship creation', 'Created scholarship "' . $title . '".');
            if ($submit) {
                logActivity($user['id'], 'Scholarship submission', 'Submitted "' . $title . '" for approval.');
            }

            $pdo->commit();

            $_SESSION['flash_success'] = $submit
                ? 'Scholarship submitted for administrator approval.'
                : 'Scholarship saved as a draft. Submit it when ready for approval.';
            redirect('provider/view-scholarship.php?id=' . $scholarshipId);
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Add scholarship error: ' . $e->getMessage());
            $errors[] = 'Could not save the scholarship. Please try again.';
        }
    }
}

$page_title = 'Add Scholarship';
$layout = 'dashboard';
$page_active = 'add';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-1">Add Scholarship</h4>
                <p class="text-muted">Fill in the details, add requirements and documents, then submit for approval.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $er): ?><li><?php echo e($er); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo url('provider/add-scholarship.php'); ?>">
                    <?php echo csrfField(); ?>

                    <div class="card mb-4">
                        <div class="card-header"><i class="bi bi-pencil-square me-1"></i>Scholarship Details</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label required">Title</label>
                                    <input type="text" class="form-control" name="title" value="<?php echo e($_POST['title'] ?? ''); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label required">Description</label>
                                    <textarea class="form-control" name="description" rows="4" required><?php echo e($_POST['description'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Scholarship type</label>
                                    <select class="form-select" name="scholarship_type">
                                        <option value="">Select type</option>
                                        <?php foreach (['Merit-Based', 'Need-Based', 'Leadership', 'Diversity', 'Skill-Based', 'Research Grant', 'Other'] as $t): ?>
                                            <option <?php echo ($_POST['scholarship_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Education level</label>
                                    <select class="form-select" name="education_level">
                                        <option value="">All levels</option>
                                        <?php foreach (['High School', 'Undergraduate', 'College', 'Postgraduate', 'Vocational'] as $l): ?>
                                            <option <?php echo ($_POST['education_level'] ?? '') === $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" value="<?php echo e($_POST['location'] ?? ''); ?>" placeholder="e.g. Metro Manila, National, Remote">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Amount</label>
                                    <input type="text" class="form-control" name="amount" value="<?php echo e($_POST['amount'] ?? ''); ?>" placeholder="e.g. PHP 50,000 per year">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Deadline</label>
                                    <input type="date" class="form-control" name="deadline" value="<?php echo e($_POST['deadline'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Official URL</label>
                                    <input type="url" class="form-control" name="official_url" value="<?php echo e($_POST['official_url'] ?? ''); ?>" placeholder="https://...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Benefits</label>
                                    <textarea class="form-control" name="benefits" rows="3"><?php echo e($_POST['benefits'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Eligibility summary</label>
                                    <textarea class="form-control" name="eligibility_summary" rows="3"><?php echo e($_POST['eligibility_summary'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Requirements -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-check2-square me-1"></i>Requirements</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('requirements','req-rows')"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                        <div class="card-body">
                            <div id="req-rows">
                                <div class="row g-2 req-row">
                                    <div class="col-11"><input type="text" class="form-control" name="requirements[]" placeholder="e.g. Must be an undergraduate student"></div>
                                    <div class="col-1"><button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-file-earmark-text me-1"></i>Required Documents</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDoc()"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                        <div class="card-body">
                            <div id="doc-rows">
                                <div class="row g-2 doc-row">
                                    <div class="col-md-4"><input type="text" class="form-control" name="doc_name[]" placeholder="Document name"></div>
                                    <div class="col-md-7"><input type="text" class="form-control" name="doc_description[]" placeholder="Description (optional)"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mb-4">
                        <a class="btn btn-outline-secondary" href="<?php echo url('provider/scholarships.php'); ?>">Cancel</a>
                        <button type="submit" name="submit_action" value="draft" class="btn btn-secondary">
                            <i class="bi bi-save me-1"></i>Save as Draft
                        </button>
                        <button type="submit" name="submit_action" value="submit" class="btn btn-primary-soft">
                            <i class="bi bi-send me-1"></i>Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function addRow(id, containerId) {
    const container = document.getElementById(containerId);
    const rows = container.querySelectorAll('.' + id + '-row');
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
