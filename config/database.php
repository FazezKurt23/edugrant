<?php
/**
 * EduGrant — Database Connection (PDO)
 *
 * Returns a shared PDO connection object. Used across the entire application.
 * Do NOT display the credentials in the UI.
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host     = '127.0.0.1';
    $port     = '3306';
    $dbname   = 'edugrant';
    $user     = 'root';
    $password = ''; // default XAMPP MySQL has no root password

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Log the technical detail to a file, never expose it to the user.
        error_log('DB connection error: ' . $e->getMessage());
        http_response_code(500);
        die('Database connection failed. Please check that MySQL is running and the database has been imported. See the README for troubleshooting.');
    }

    return $pdo;
}
