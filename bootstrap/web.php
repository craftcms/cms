<?php
/**
 * Craft web bootstrap file.
 *
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

// Check for this early because Craft uses it before the requirements checker gets a chance to run.
if (!extension_loaded('mbstring') || (extension_loaded('mbstring') && ini_get('mbstring.func_overload') != 0)) {
    exit('Craft CMS requires the <a href="http://php.net/manual/en/book.mbstring.php" target="_blank">PHP multibyte string</a> extension in order to run. Please talk to your host/IT department about enabling it on your server.');
}

// PHP environment normalization
// -----------------------------------------------------------------------------

// These have been deprecated in PHP 5.6 in favor of default_charset, which defaults to 'UTF-8'
// http://php.net/manual/en/migration56.deprecated.php
if (PHP_VERSION_ID < 50600) {
    // Set MB to use UTF-8
    mb_internal_encoding('UTF-8');
    mb_regex_encoding('UTF-8');
    mb_http_input('UTF-8');
    mb_http_output('UTF-8');
}

mb_detect_order('auto');

// Normalize how PHP's string methods (strtoupper, etc) behave.
setlocale(
    LC_CTYPE,
    'C.UTF-8', // libc >= 2.13
    'C.utf8', // different spelling
    'en_US.UTF-8', // fallback to lowest common denominator
    'en_US.utf8' // different spelling for fallback
);

// Set default timezone to UTC
date_default_timezone_set('UTC');

// Load Craft
// -----------------------------------------------------------------------------

$appType = 'web';

return require __DIR__.'/bootstrap.php';
