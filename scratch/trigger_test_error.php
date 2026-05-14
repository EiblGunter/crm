<?php
// Mock DOCUMENT_ROOT
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__) . '/httpdocs';
}

// Mock POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'save';
$_POST['id'] = 1;
$_POST['field'] = 'vorname';
$_POST['value'] = 'error_test';

echo "Executing adresse_neu.php with error_test value...\n";

// Include the application (it will exit at the end of the AJAX handler)
require_once $_SERVER['DOCUMENT_ROOT'] . '/appl/adresse/adresse_neu.php';
