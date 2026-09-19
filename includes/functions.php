<?php
/**
 * EduGrant — Reusable Helper Functions
 *
 * Contains all shared, non-authentication helper functions.
 * Authentication helpers live in includes/auth.php.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/* ------------------------------------------------------------------
 * Output & URL helpers
 * ------------------------------------------------------------------ */

/**
 * Bootstrap badge color for application tracking statuses.
 */
function appStatusBadge(string $status): string
{
    $map = [
        'interested'  => 'secondary',
        'preparing'   => 'info',
        'applied'     => 'primary',
        'under_review'=> 'warning text-dark',
        'approved'    => 'success',
        'rejected'    => 'danger',
    ];
    return $map[$status] ?? 'secondary';
}

/**
 * Bootstrap badge color for scholarship statuses.
 */
function scholarshipStatusBadge(string $status): string
{
    $map = [
        'draft'    => 'secondary',
        'pending'  => 'warning text-dark',
        'approved' => 'success',
        'rejected' => 'danger',
        'expired'  => 'dark',
    ];
    return $map[$status] ?? 'secondary';
}

/**
 * Build a full application URL.
 */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Redirect to a relative path or full URL and stop execution.
 */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Escape output for safe HTML display.
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Basic server-side string sanitizer used on raw input.
 */
function clean(string $value): string
{
    return trim(strip_tags($value));
}

/**
 * Normalize a user-entered numeric value by stripping thousand separators,
 * currency symbols, and whitespace. e.g. "250,000" -> "250000", "₱1.75" -> "1.75".
 * Returns the cleaned string, or '' if the input is empty.
 */
function normalizeNumeric(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    // Remove common currency symbols
    $value = str_replace(['₱', 'PHP', 'php', 'P', ','], '', $value);
    return trim($value);
}

/**
 * Validate a URL (allows empty). Returns false when invalid.
 */
function validateUrl(?string $value): bool
{
    if ($value === null || trim($value) === '') {
        return true;
    }
    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower(parse_url($value, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

/* ------------------------------------------------------------------
 * CSRF protection
 * ------------------------------------------------------------------ */

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCsrfToken(): string
{
    return generateCsrfToken();
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify the CSRF token from POST. On failure, sets an error and
 * redirects back to $fallback.
 */
function requireCsrf(string $fallback = ''): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $_SESSION['flash_error'] = 'Invalid security token. Please try again.';
        redirect($fallback !== '' ? $fallback : 'index.php');
    }
}

/* ------------------------------------------------------------------
 * Flash messages
 * ------------------------------------------------------------------ */

function flash(string $key = 'flash_success'): void
{
    if (!empty($_SESSION[$key])) {
        echo '<div class="alert alert-' . e($key === 'flash_error' ? 'danger' : 'success') .
             ' alert-dismissible fade show" role="alert">' . e($_SESSION[$key]) .
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION[$key]);
    }
}

/* ------------------------------------------------------------------
 * Dates & deadlines
 * ------------------------------------------------------------------ */

function formatDate(?string $date, string $format = 'F j, Y'): string
{
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

/**
 * Compute the state of a scholarship deadline.
 *
 * Returns:
 *   days_remaining : integer (negative when past)
 *   label          : Upcoming / Approaching / Urgent / Expired / No deadline
 *   badge          : bootstrap badge color
 *   deadline       : formatted date
 *   expired        : bool
 */
function getDeadlineState(?string $deadline): array
{
    $empty = [
        'days_remaining' => null,
        'label'          => 'No deadline',
        'badge'          => 'secondary',
        'deadline'       => '—',
        'expired'        => false,
    ];

    if (empty($deadline)) {
        return $empty;
    }

    $ts        = strtotime($deadline);
    $today     = strtotime(date('Y-m-d'));
    $days      = (int)round(($ts - $today) / 86400);

    if ($days <= 0) {
        $label = 'Expired';
        $badge = 'dark';
        $expired = true;
    } elseif ($days <= 7) {
        $label = 'Urgent';
        $badge = 'danger';
        $expired = false;
    } elseif ($days <= 30) {
        $label = 'Approaching';
        $badge = 'warning text-dark';
        $expired = false;
    } else {
        $label = 'Upcoming';
        $badge = 'success';
        $expired = false;
    }

    return [
        'days_remaining' => $days,
        'label'          => $label,
        'badge'          => $badge,
        'deadline'       => date('F j, Y', $ts),
        'expired'        => $expired,
    ];
}

/* ------------------------------------------------------------------
 * Notifications
 * ------------------------------------------------------------------ */

function notifIcon(string $type): string
{
    $map = [
        'deadline'    => 'alarm',
        'application' => 'kanban',
        'scholarship' => 'collection',
        'approval'    => 'check-circle',
        'rejection'   => 'x-circle',
        'system'      => 'info-circle',
    ];
    return $map[$type] ?? 'info-circle';
}

function timeAgo(?string $datetime): string
{
    if (empty($datetime)) {
        return '';
    }
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minute(s) ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour(s) ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day(s) ago';
    return date('M j, Y', $ts);
}

function createNotification(int $userId, string $title, string $message, string $type = 'system'): bool
{
    $allowed = ['deadline', 'application', 'scholarship', 'system', 'approval', 'rejection'];
    if (!in_array($type, $allowed, true)) {
        $type = 'system';
    }
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $message, $type]);
        return true;
    } catch (Throwable $e) {
        error_log('createNotification error: ' . $e->getMessage());
        return false;
    }
}

function getUnreadNotifications(int $userId): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, title, message, type, is_read, created_at
         FROM notifications
         WHERE user_id = ? AND is_read = 0
         ORDER BY created_at DESC
         LIMIT 5'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUnreadNotificationCount(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetch()['c'];
}

function getNotifications(int $userId, int $limit = 50): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/* ------------------------------------------------------------------
 * Activity logs
 * ------------------------------------------------------------------ */

function logActivity(?int $userId, string $action, string $description): void
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $action, $description]);
    } catch (Throwable $e) {
        error_log('logActivity error: ' . $e->getMessage());
    }
}

/* ------------------------------------------------------------------
 * Scholarships
 * ------------------------------------------------------------------ */

/**
 * Fetch a single scholarship. $includePending lets admins/providers
 * view non-approved scholarships.
 */
function getScholarship(int $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT s.*, p.organization_name, p.verification_status
         FROM scholarships s
         JOIN provider_profiles p ON p.id = s.provider_id
         WHERE s.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Scholarships visible to students: approved and not past deadline.
 */
function getStudentVisibleScholarships(int $id): ?array
{
    $row = getScholarship($id);
    if (!$row) {
        return null;
    }
    $state = getDeadlineState($row['deadline']);
    if ($row['status'] !== 'approved' || $state['expired']) {
        return null;
    }
    return $row;
}

function getScholarshipRequirements(int $scholarshipId): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM scholarship_requirements WHERE scholarship_id = ? ORDER BY id ASC');
    $stmt->execute([$scholarshipId]);
    return $stmt->fetchAll();
}

function getRequiredDocuments(int $scholarshipId): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM required_documents WHERE scholarship_id = ? ORDER BY id ASC');
    $stmt->execute([$scholarshipId]);
    return $stmt->fetchAll();
}

function isScholarshipSaved(int $scholarshipId, int $studentId): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM saved_scholarships WHERE student_id = ? AND scholarship_id = ?');
    $stmt->execute([$studentId, $scholarshipId]);
    return (int)$stmt->fetch()['c'] > 0;
}

function getStudentApplication(int $scholarshipId, int $studentId): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM applications WHERE student_id = ? AND scholarship_id = ?');
    $stmt->execute([$studentId, $scholarshipId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * A safe, controlled mechanism that flags approved scholarships whose
 * deadline has passed as "expired". Called only from admin dashboard load.
 */
function expirePastDeadlineScholarships(): int
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            "UPDATE scholarships SET status = 'expired'
             WHERE status = 'approved' AND deadline IS NOT NULL AND deadline < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Throwable $e) {
        error_log('expirePastDeadlineScholarships error: ' . $e->getMessage());
        return 0;
    }
}

/* ------------------------------------------------------------------
 * Student / provider profile helpers
 * ------------------------------------------------------------------ */

function getStudentProfileByUserId(int $userId): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getProviderProfileByUserId(int $userId): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM provider_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getStudentProfile(int $studentId): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE id = ?');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* ------------------------------------------------------------------
 * Eligibility checker
 * ------------------------------------------------------------------ */

/**
 * Simple, honest eligibility guide based on structured profile data.
 *
 * Returns a list of items: [label, matches(bool|null), note].
 * null means "Requires Manual Review".
 */
function checkEligibility(array $profile, array $scholarship): array
{
    $items = [];
    $gpa   = isset($profile['gpa']) && $profile['gpa'] !== null ? (float)$profile['gpa'] : null;
    $income = isset($profile['family_income']) && $profile['family_income'] !== null ? (float)$profile['family_income'] : null;

    // Education level
    $items[] = [
        'label' => 'Education level',
        'status' => null,
        'note' => 'Requires manual review — compare "' . e($scholarship['education_level'] ?? 'N/A') .
                  '" with your current enrollment level.',
    ];

    // Course / program keywords
    $courseKeywords = ['it', 'computer', 'science', 'engineering', 'stem', 'technology', 'tech'];
    $course = strtolower((string)($profile['course'] ?? ''));
    $scholarshipText = strtolower(
        ($scholarship['title'] ?? '') . ' ' . ($scholarship['description'] ?? '')
    );
    $programSpecific = false;
    foreach ($courseKeywords as $kw) {
        if (strpos($scholarshipText, $kw) !== false) {
            $programSpecific = true;
            break;
        }
    }
    if ($programSpecific) {
        $courseHasKeyword = false;
        foreach (['it', 'computer', 'cs', 'science', 'engineering', 'stem', 'information technology'] as $kw) {
            if (strpos($course, $kw) !== false) {
                $courseHasKeyword = true;
                break;
            }
        }
        $items[] = [
            'label' => 'Course / program match',
            'status' => $courseHasKeyword,
            'note' => $courseHasKeyword
                ? 'Your course (' . e($profile['course']) . ') appears related to this program.'
                : 'Your course may not match the program focus of this scholarship.',
        ];
    } else {
        $items[] = [
            'label' => 'Course / program',
            'status' => null,
            'note' => 'No strong program restriction detected from structured data.',
        ];
    }

    // GPA check
    if ($gpa !== null) {
        $items[] = [
            'label' => 'GPA',
            'status' => $gpa <= 2.00,
            'note' => 'Your reported GPA is ' . number_format($gpa, 2) .
                      '. Many scholarships expect a general weighted average of 2.00 or better.',
        ];
    } else {
        $items[] = [
            'label' => 'GPA',
            'status' => null,
            'note' => 'Requires manual review — update your GPA in your profile to use this check.',
        ];
    }

    // Family income (need-based)
    if (stripos(($scholarship['eligibility_summary'] ?? ''), 'income') !== false ||
        stripos(($scholarship['description'] ?? ''), 'financial need') !== false) {
        if ($income !== null) {
            $items[] = [
                'label' => 'Family income',
                'status' => $income <= 300000,
                'note' => 'Your reported family income is PHP ' . number_format($income, 2) .
                          '. Need-based scholarships usually target lower-income households.',
            ];
        } else {
            $items[] = [
                'label' => 'Family income',
                'status' => null,
                'note' => 'Requires manual review — update your family income in your profile.',
            ];
        }
    }

    // Location / residency
    if (!empty($scholarship['location'])) {
        $items[] = [
            'label' => 'Location / residency',
            'status' => null,
            'note' => 'The scholarship lists location "' . e($scholarship['location']) .
                      '". Confirm that you satisfy any residency requirement.',
        ];
    }

    // Deadline
    $state = getDeadlineState($scholarship['deadline']);
    $items[] = [
        'label' => 'Deadline',
        'status' => !$state['expired'],
        'note' => $state['expired']
            ? 'This scholarship deadline has passed.'
            : 'Deadline is ' . $state['deadline'] . ' (' . $state['days_remaining'] . ' days remaining).',
    ];

    return $items;
}

/**
 * Produce a summary verdict from eligibility items.
 */
function eligibilityVerdict(array $items): array
{
    $verified = 0;
    $failed = 0;
    $manual = 0;
    foreach ($items as $item) {
        if ($item['status'] === true) {
            $verified++;
        } elseif ($item['status'] === false) {
            $failed++;
        } else {
            $manual++;
        }
    }
    if ($failed > 0) {
        return ['verdict' => 'May Not Meet Requirements', 'badge' => 'warning text-dark', 'summary' => 'Some checked requirements were not met based on your profile.'];
    }
    if ($verified >= 2 && $manual === 0) {
        return ['verdict' => 'Likely Eligible', 'badge' => 'success', 'summary' => 'Your profile matches the checked requirements.'];
    }
    return ['verdict' => 'Requires Manual Review', 'badge' => 'info', 'summary' => 'Some requirements could not be verified automatically. Review the scholarship requirements carefully.'];
}
