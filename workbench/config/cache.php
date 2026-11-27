<?php

declare(strict_types=1);

use function Orchestra\Testbench\default_skeleton_path;

// Testbench's cache config, with the file store by default rather than the
// array store, which the workbench server forgets between requests. Tests set
// `CACHE_STORE` to `array`.
return [
    ...require default_skeleton_path('config/cache.php'),
    'default' => env('CACHE_STORE', 'file'),
];
