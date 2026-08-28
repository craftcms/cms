<?php

declare(strict_types=1);

return [
    'browser_logs_watcher' => false,
    'enforce_tests' => false,
    'rules' => [
        'enabled' => true,
        'scoped_guidelines' => false,
    ],
    'guidelines' => [
        'exclude' => [
            'deployments',
            'laravel/core',
            'pest/core',
        ],
    ],
    'skills' => [
        'exclude' => [
            'pest-testing',
            'testing-best-practices',
        ],
    ],
];
