<?php
/**
 * Database connection. db() returns one shared PDO instance per request,
 * always using prepared statements from the calling code - this file
 * itself never builds a query, it only opens the connection.
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            app_log('Database connection failed: ' . $e->getMessage());
            show_friendly_error(
                'We are having a technical problem',
                'Please try again in a few minutes. If this keeps happening, the site administrator has been notified.'
            );
        }
    }

    return $pdo;
}
