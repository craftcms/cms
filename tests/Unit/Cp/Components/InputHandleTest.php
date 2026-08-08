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
        ->toContain('<craft-input-handle name="handle">')
        ->toContain('name="handle"')
        ->toContain('value="exampleHandle"')
        ->toContain('autocorrect="off"')
        ->toContain('autocapitalize="none"')
        ->and(app(ComponentRegistry::class)->make('input-handle'))
        ->toBeInstanceOf(InputHandle::class);
});
