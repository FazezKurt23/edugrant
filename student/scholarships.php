<?php
/**
 * EduGrant — Scholarship Directory (Student)
 *
 * Real database search + filter + sort + pagination.
 * Only approved, non-expired scholarships are shown.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requireRole('student');

$user = getCurrentUser();
$me   = getStudentProfileByUserId($user['id']);
$pdo  = db();

// ---- Handle save / unsave ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('student/scholarships.php');
    $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $scholarship = getStudentVisibleScholarships($scholarshipId);

    if ($scholarship) {
        if ($action === 'save') {
            try {
                $stmt = $pdo->prepare('INSERT INTO saved_scholarships (student_id, scholarship_id) VALUES (?, ?)');
                $stmt->execute([$me['id'], $scholarshipId]);
                logActivity($user['id'], 'Save scholarship', 'Saved "' . $scholarship['title'] . '".');
                $_SESSION['flash_success'] = 'Scholarship saved to your list.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = 'You have already saved this scholarship.';
            }
        } elseif ($action === 'unsave') {
            $stmt = $pdo->prepare('DELETE FROM saved_scholarships WHERE student_id = ? AND scholarship_id = ?');
            $stmt->execute([$me['id'], $scholarshipId]);
            logActivity($user['id'], 'Unsave scholarship', 'Removed "' . $scholarship['title'] . '" from saved list.');
            $_SESSION['flash_success'] = 'Scholarship removed from your saved list.';
        }
    } else {
        $_SESSION['flash_error'] = 'Scholarship not found or no longer available.';
    }
    redirect('student/scholarships.php');
}

// ---- Filters ----
$search  = clean($_GET['q'] ?? '');
$type    = clean($_GET['type'] ?? '');
$edu     = clean($_GET['education_level'] ?? '');
$loc     = clean($_GET['location'] ?? '');
$deadline= clean($_GET['deadline'] ?? '');
$sort    = clean($_GET['sort'] ?? 'newest');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = PAGE_SIZE;
$offset = ($page - 1) * $limit;

$where  = ["s.status = 'approved'", "(s.deadline IS NULL OR s.deadline >= CURDATE())"];
$params = [];

if ($search !== '') {
    $where[] = '(s.title LIKE ? OR s.description LIKE ? OR p.organization_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if ($type !== '') {
    $where[] = 's.scholarship_type = ?';
    $params[] = $type;
}
if ($edu !== '') {
    $where[] = 's.education_level = ?';
    $params[] = $edu;
}
if ($loc !== '') {
    $where[] = 's.location LIKE ?';
    $params[] = '%' . $loc . '%';
}
if ($deadline === '30') {
    $where[] = 's.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
} elseif ($deadline === '90') {
    $where[] = 's.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
} elseif ($deadline === 'all') {
    // no extra condition — include all active
}

$orderBy = match ($sort) {
    'deadline_soonest' => 's.deadline ASC, s.title ASC',
    'deadline_latest'  => 's.deadline DESC, s.title ASC',
    'name_az'          => 's.title ASC',
    default            => 's.created_at DESC',
};

$whereSql = implode(' AND ', $where);

// Count total for pagination
$countSql = "SELECT COUNT(*) FROM scholarships s JOIN provider_profiles p ON p.id = s.provider_id WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));
if ($page > $pages) {
    $page = $pages;
    $offset = ($page - 1) * $limit;
}

$sql = "SELECT s.*, p.organization_name,
        EXISTS(
            SELECT 1 FROM saved_scholarships ss
            WHERE ss.student_id = {$me['id']} AND ss.scholarship_id = s.id
        ) AS is_saved
        FROM scholarships s
        JOIN provider_profiles p ON p.id = s.provider_id
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$scholarships = $stmt->fetchAll();

// Filter options (distinct values from DB)
$types = $pdo->query("SELECT DISTINCT scholarship_type FROM scholarships WHERE scholarship_type IS NOT NULL AND scholarship_type <> '' ORDER BY scholarship_type")->fetchAll(PDO::FETCH_COLUMN);
$edus  = $pdo->query("SELECT DISTINCT education_level FROM scholarships WHERE education_level IS NOT NULL AND education_level <> '' ORDER BY education_level")->fetchAll(PDO::FETCH_COLUMN);
$locs  = $pdo->query("SELECT DISTINCT location FROM scholarships WHERE location IS NOT NULL AND location <> '' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

// Rebuild query string for pagination
$qs = [];
foreach (['q', 'type', 'education_level', 'location', 'deadline', 'sort'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') {
        $qs[] = urlencode($k) . '=' . urlencode($_GET[$k]);
    }
}
$qs = implode('&', $qs);

$page_title = 'Find Scholarships';
$layout = 'dashboard';
$page_active = 'scholarships';
include BASE_PATH . '/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex">
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        <div class="flex-grow-1 dashboard-content">
            <div class="px-3 px-lg-4">
                <h4 class="fw-bold text-navy mb-1">Find Scholarships</h4>
                <p class="text-muted">Browse approved scholarship opportunities. Deadlines are calculated automatically.</p>

                <form method="get" action="<?php echo url('student/scholarships.php'); ?>" class="card card-body mb-4">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="q" placeholder="Search title, provider, description..."
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="type">
                                <option value="">All types</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo e($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo e($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="education_level">
                                <option value="">All levels</option>
                                <?php foreach ($edus as $ed): ?>
                                    <option value="<?php echo e($ed); ?>" <?php echo $edu === $ed ? 'selected' : ''; ?>><?php echo e($ed); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="deadline">
                                <option value="">Any deadline</option>
                                <option value="30" <?php echo $deadline === '30' ? 'selected' : ''; ?>>Within 30 days</option>
                                <option value="90" <?php echo $deadline === '90' ? 'selected' : ''; ?>>Within 90 days</option>
                                <option value="all" <?php echo $deadline === 'all' ? 'selected' : ''; ?>>All active</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                <option value="deadline_soonest" <?php echo $sort === 'deadline_soonest' ? 'selected' : ''; ?>>Deadline soonest</option>
                                <option value="deadline_latest" <?php echo $sort === 'deadline_latest' ? 'selected' : ''; ?>>Deadline latest</option>
                                <option value="name_az" <?php echo $sort === 'name_az' ? 'selected' : ''; ?>>Name A–Z</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('student/scholarships.php'); ?>">Reset</a>
                            <button type="submit" class="btn btn-primary-soft btn-sm px-3">Apply Filters</button>
                        </div>
                    </div>
                </form>

                <div class="text-muted small mb-3"><?php echo $total; ?> scholarship(s) found.</div>

                <?php if (empty($scholarships)): ?>
                    <div class="card empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="mt-2 mb-0">No scholarships match your search.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($scholarships as $s): $dl = getDeadlineState($s['deadline']); ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card scholar-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="badge badge-status bg-<?php echo e($dl['badge']); ?>"><?php echo e($dl['label']); ?></span>
                                            <form method="post" action="<?php echo url('student/scholarships.php'); ?>">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="scholarship_id" value="<?php echo $s['id']; ?>">
                                                <input type="hidden" name="action" value="<?php echo $s['is_saved'] ? 'unsave' : 'save'; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $s['is_saved'] ? 'btn-warning' : 'btn-outline-primary'; ?>"
                                                        title="<?php echo $s['is_saved'] ? 'Remove from saved' : 'Save this scholarship'; ?>">
                                                    <i class="bi bi-<?php echo $s['is_saved'] ? 'bookmark-heart-fill' : 'bookmark'; ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <h6 class="scholar-title mt-2 mb-1"><?php echo e($s['title']); ?></h6>
                                        <div class="provider-name mb-2"><i class="bi bi-building me-1"></i><?php echo e($s['organization_name']); ?></div>
                                        <div class="meta-row mb-1"><i class="bi bi-mortarboard"></i><?php echo e($s['education_level'] ?: 'All levels'); ?></div>
                                        <div class="meta-row mb-1"><i class="bi bi-geo-alt"></i><?php echo e($s['location'] ?: 'N/A'); ?></div>
                                        <div class="meta-row mb-1"><i class="bi bi-cash-coin"></i><?php echo e($s['amount'] ?: 'Varies'); ?></div>
                                        <div class="meta-row mb-1"><i class="bi bi-pie-chart"></i><?php echo e($s['scholarship_type'] ?: 'General'); ?></div>
                                        <div class="deadline-chip mt-2"><i class="bi bi-alarm"></i>
                                            <?php echo e($dl['deadline']); ?>
                                            <strong>(<?php echo $dl['days_remaining'] >= 0 ? $dl['days_remaining'] . ' days' : 'Closed'; ?>)</strong>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-0 pb-3 pt-0">
                                        <a class="btn btn-primary-soft btn-sm w-100" href="<?php echo url('student/scholarship-details.php?id=' . $s['id']); ?>">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo url('student/scholarships.php?' . ($qs !== '' ? $qs . '&' : '') . 'page=' . ($page - 1)); ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo url('student/scholarships.php?' . ($qs !== '' ? $qs . '&' : '') . 'page=' . $i); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo url('student/scholarships.php?' . ($qs !== '' ? $qs . '&' : '') . 'page=' . ($page + 1)); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
