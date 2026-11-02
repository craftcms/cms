<?php

declare(strict_types=1);

use function CraftCms\Cms\template;

it('renders action shortcuts with craft-shortcut', function () {
    $html = template('_layouts/components/form-action-menu', [
        'formActions' => [
            ['label' => 'Save and continue editing', 'shortcut' => true],
            ['label' => 'Save as a new entry', 'shortcut' => true, 'shift' => true],
            ['label' => 'Delete', 'destructive' => true],
        ],
    ]);

    expect($html)
        ->toContainTag('craft-shortcut', ['shift' => true])
        ->not->toContain('class="shortcut"')
        ->and(substr_count($html, '<craft-shortcut'))->toBe(2)
        ->and(substr_count($html, ' shift>S</craft-shortcut>'))->toBe(1);
});
