<?php
/**
 * Craft console bootstrap file.
 *
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use yii\console\ExitCode;

// Make sure they're running PHP 7+
if (PHP_VERSION_ID < 70000) {
    echo "Craft requires PHP 7.0 or later.\n";
    exit(ExitCode::UNSPECIFIED_ERROR);
}

// Make sure $_SERVER['SCRIPT_FILENAME'] is set
if (!isset($_SERVER['SCRIPT_FILENAME'])) {
    $trace = debug_backtrace(0);
    if (($first = end($trace)) !== false && isset($first['file'])) {
        $_SERVER['SCRIPT_FILENAME'] = $first['file'];
    }
}

mb_detect_order('auto');

// Normalize how PHP's string methods (strtoupper, etc) behave.
if (PHP_VERSION_ID < 70300) {
    setlocale(
        LC_CTYPE,
        'C.UTF-8', // libc >= 2.13
        'C.utf8', // different spelling
        'en_US.UTF-8', // fallback to lowest common denominator
        'en_US.utf8' // different spelling for fallback
    );
} else {
    // https://github.com/craftcms/cms/issues/4239
    setlocale(
        LC_CTYPE,
        'C.UTF-8', // libc >= 2.13
        'C.utf8' // different spelling
    );
}

// Set default timezone to UTC
date_default_timezone_set('UTC');

$appType = 'console';

return require __DIR__ . '/bootstrap.php';
