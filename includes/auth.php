<?php
/**
 * EduGrant — Authentication & Authorization Helpers (Fixed Admin Redirection)
 *
 * Handles session-based auth, role checks, and access control.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * True when a user is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user record (from session data only).
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'     => (int)$_SESSION['user_id'],
        'name'   => $_SESSION['user_name'] ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role'] ?? '',
    ];
}

/**
 * Require login. Redirects guests to the login page.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        redirect('login.php');
    }
}

/**
 * Require a specific role. Also prevents suspended/inactive accounts.
 */
function requireRole(string $role): void
{
    requireLogin();

    $user = getCurrentUser();
    if ($user['role'] !== $role) {
        http_response_code(403);
        die('403 Forbidden — You do not have permission to access this page.');
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT TRIM(status) FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $status = $stmt->fetchColumn();

    if (trim($status) !== 'active') {
        session_destroy();
        redirect('login.php?blocked=1');
    }
}

/**
 * Full current user row from the database (refreshed status).
 */
function currentUserRow(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Perform login: verify credentials, regenerate session, set session vars.
 *
 * @return array ['success' => bool, 'message' => string, 'redirect' => string]
 */
function attemptLogin(string $email, string $password): array
{
    $pdo = db();

    // Gigamitan og TRIM() ang email aron mapapas ang bisan unsang hidden spaces sa database
    $stmt = $pdo->prepare('SELECT id, name, email, password, TRIM(role) as role, TRIM(status) as status FROM users WHERE TRIM(email) = ?');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    // Gilimpyohan ang password string gikan sa database para sigurado ang password_verify
    $db_password = isset($user['password']) ? trim($user['password']) : '';

    if (!$user || !password_verify($password, $db_password)) {
        return ['success' => false, 'message' => 'Invalid email or password.', 'redirect' => ''];
    }

    if ($user['status'] !== 'active') {
        return [
            'success' => false,
            'message' => 'Your account is ' . $user['status'] . '. Please contact the administrator.',
            'redirect' => '',
        ];
    }

    session_regenerate_id(true);
    session_unset();

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    logActivity((int)$user['id'], 'Login', 'User logged in.');

    $route = [
        'student'  => 'student/dashboard.php',
        'provider' => 'provider/dashboard.php',
        'admin'    => 'admin/dashboard.php',
    ];

    return [
        'success' => true,
        'message' => 'Welcome back, ' . $user['name'] . '!',
        'redirect' => $route[$user['role']] ?? 'index.php',
    ];
}

/**
 * Secure logout.
 */
function performLogout(): void
{
    if (isLoggedIn()) {
        logActivity((int)$_SESSION['user_id'], 'Logout', 'User logged out.');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirect('login.php');
}