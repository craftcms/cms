<?php

declare(strict_types=1);

return [
    'disks' => [
        'assets' => [
            'driver' => 'local',
            'root' => public_path('assets'),
            'url' => '/assets',
            'visibility' => 'public',
        ],
    ],
];
