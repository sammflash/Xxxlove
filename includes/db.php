<?php
/**
 * Single shared PDO connection. Include this once per request; call
 * db() wherever a query is needed.
 */

require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Never leak DSN/credentials or the raw driver message to visitors.
            error_log('[db] connection failed: ' . $e->getMessage());
            http_response_code(500);
            require __DIR__ . '/../errors/500.php';
            exit;
        }
    }

    return $pdo;
}
