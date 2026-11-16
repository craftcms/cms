<?php

declare(strict_types=1);

use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\FieldContext;

it('tells its control whether options show only their icons', function (bool $iconsOnly) {
    $field = new ButtonGroup([
        'iconsOnly' => $iconsOnly,
        'options' => [
            ['label' => 'Left', 'value' => 'left', 'icon' => 'arrow-left', 'default' => false],
        ],
    ]);

    $props = $field->uiControl(new FieldContext('value'))->props();

    expect($props['options'][0]['icon'])->toBe('arrow-left')
        ->and($props['iconsOnly'] ?? true)->toBe($iconsOnly);
})->with([
    'icons only' => true,
    'icons with labels' => false,
]);
