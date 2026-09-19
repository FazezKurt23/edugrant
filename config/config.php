<?php
/**
 * EduGrant — Application Configuration
 *
 * Central configuration constants used across the whole application.
 */

// Base URL. Change this if EduGrant is not installed at
// http://localhost/EduGrant/
define('BASE_URL', 'http://localhost/edugrant-111');

// Absolute filesystem path to the project root
define('BASE_PATH', __DIR__ . '/..');

// Application name / brand
define('APP_NAME', 'EduGrant');
define('APP_TAGLINE', 'Find Opportunities. Build Your Future.');

// Session lifetime (seconds). 2 hours.
define('SESSION_LIFETIME', 7200);

// Development mode.
// When true, the password-reset flow displays the reset link directly on
// screen instead of requiring a real mail server.
define('DEV_MODE', true);

// Pagination
define('PAGE_SIZE', 8);

// Default timezone
date_default_timezone_set('Asia/Manila');

// Start a secure session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false, // set to true if you use HTTPS
    ]);
    session_start();
}

// Server-side idle timeout: log out users inactive longer than SESSION_LIFETIME.
if (isset($_SESSION['user_id'])) {
    $lastActivity = $_SESSION['last_activity'] ?? time();
    if ((time() - (int)$lastActivity) > SESSION_LIFETIME) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your session expired due to inactivity. Please log in again.';
    } else {
        $_SESSION['last_activity'] = time();
    }
}
