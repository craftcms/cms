<?php
/**
 * Craft web bootstrap file.
 *
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

// Make sure they're running PHP 8+
if (PHP_VERSION_ID < 80002) {
    exit('Craft requires PHP 8.0.2 or later.');
}

// Check for this early because Craft uses it before the requirements checker gets a chance to run.
if (!extension_loaded('mbstring') || ini_get('mbstring.func_overload') != 0) {
    exit('Craft requires the <a href="https://php.net/manual/en/book.mbstring.php" rel="noopener" target="_blank">PHP multibyte string</a> extension in order to run. Please talk to your host/IT department about enabling it on your server.');
}

// PHP environment normalization
// -----------------------------------------------------------------------------

mb_detect_order('auto');

// https://github.com/craftcms/cms/issues/4239
setlocale(
    LC_CTYPE,
    'C.UTF-8', // libc >= 2.13
    'C.utf8' // different spelling
);

// Set default timezone to UTC
date_default_timezone_set('UTC');

// Load Craft
// -----------------------------------------------------------------------------

$appType = 'web';

return require __DIR__ . '/bootstrap.php';
