<?php

declare(strict_types=1);

use CraftCms\Cms\Update\Data\UpdaterState;

test('serializes updater state and options', function () {
    $state = [
        'data' => 'encrypted',
        'finishUrl' => 'https://example.test/admin/updates/finish',
        'error' => 'Choose an option',
        'options' => [
            [
                'label' => 'Continue',
                'nextUrl' => 'https://example.test/admin/updates/migrate',
                'submit' => false,
            ],
        ],
    ];

    expect(UpdaterState::fromArray($state)->toArray())->toEqual($state);
});
