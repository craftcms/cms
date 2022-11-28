<?php

// Allow the PHP development server to route requests to static files
$parts = parse_url($_SERVER['REQUEST_URI'] ?? '');
if (!empty($parts['path'])) {
    if (realpath($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $parts['path'])) {
        return false;
    }
}

// Yii expects the SCRIPT_FILENAME to come out of the document root or the Request object
// can't find itself. So we'll fake it and make it think we're accessing the index
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';

// Run Craft as normal
include $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
