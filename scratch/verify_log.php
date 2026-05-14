<?php
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__) . '/httpdocs';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$res = db_query("SELECT * FROM crm_log ORDER BY id DESC LIMIT 1");

if ($res['success'] && !empty($res['data'])) {
    $last_log = $res['data'][0];
    echo "Last Log Entry:\n";
    print_r($last_log);
} else {
    echo "No log entry found or error: " . ($res['error'] ?? 'empty');
}
