<?php
/**
 * CarniHub — Database Configuration
 * PDO Singleton for MySQL 5.7
 *
 * EDIT these credentials before deploying.
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'idactivo_carnihubdb');
define('DB_USER',    'carnihubdb_admin');
define('DB_PASS',    'mi_contraseña');
define('DB_CHARSET', 'utf8mb4');

class Database
{
    /** @var PDO|null */
    private static $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log and display a friendly error
                error_log('[CarniHub DB] ' . $e->getMessage());
                http_response_code(500);
                die(json_encode(['error' => 'Database connection failed. Check config/database.php']));
            }
        }
        return self::$instance;
    }

    // Prevent external instantiation / cloning
    private function __construct() {}
    private function __clone()     {}
}
