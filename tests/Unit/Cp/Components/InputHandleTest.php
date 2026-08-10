<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\InputHandle;

it('renders the handle input component with handle-safe text behavior', function () {
    $html = InputHandle::make()
        ->id('handle')
        ->name('handle')
        ->value('exampleHandle')
        ->toHtml();

    expect($html)
        ->toContainTag('craft-input-handle', [
            'name' => 'handle',
            'autocorrect' => 'off',
            'autocapitalize' => 'off',
        ])
        ->toContainTag('input', [
            'name' => 'handle',
            'value' => 'exampleHandle',
            'autocorrect' => 'off',
            'autocapitalize' => 'none',
        ])
        ->and(app(ComponentRegistry::class)->make('input-handle'))
        ->toBeInstanceOf(InputHandle::class);
});
