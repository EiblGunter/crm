<?php
/**
 * Database Initialization
 * Uses ag_database_layer (procedural db_* functions)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../tools/db/db.php';

// Load .env
$envPath = $_SERVER['DOCUMENT_ROOT'] . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(sprintf('%s=%s', trim($name), trim($value)));
        }
    }
}

// Prepare config array (NO [] syntax!)
$mysql_config = array(
    'host'    => getenv('MYSQL_HOST') ?: '127.0.0.1',
    'port'    => getenv('MYSQL_PORT') ?: '3308',
    'db'      => getenv('MYSQL_DATABASE') ?: 'dev_db',
    'user'    => getenv('MYSQL_USER') ?: 'dev_user',
    'pass'    => getenv('MYSQL_PASSWORD') ?: 'dev_password',
    'charset' => 'utf8mb4'
);

// Establish connection
$db_result = db_connect($mysql_config, 'default');

// Export connection status for UI if needed
if (!$db_result['success']) {
    $db_connection_error = $db_result['error'];
}
?>