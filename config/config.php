<?php
/**
 * EduGrant — Application Configuration
 *
 * Central configuration constants used across the whole application.
 */

// Base URL. Change this if EduGrant is not installed at
// http://localhost/EduGrant/
define('BASE_URL', 'http://localhost/EduGrant');

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
