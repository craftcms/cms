<?php

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

$appType = 'console';

return require __DIR__.'/bootstrap.php';
