<?php

// Allow the PHP development server to route requests to static files
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($path && realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . urldecode($path))) {
    return false;
}

// Yii expects the SCRIPT_FILENAME to come out of the document root or the Request object
// can't find itself. So we'll fake it and make it think we're accessing the index
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';

// Run Craft as normal
include $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
