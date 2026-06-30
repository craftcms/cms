<?php

declare(strict_types=1);

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\Blade\Directives\NamespaceDirective;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

it('namespaces captured Blade output', function () {
    $output = $this->renderer->renderString(<<<'BLADE'
@craftNamespace('settings')
<label for="title">Title</label><input id="title" name="title">
@endCraftNamespace
BLADE);

    expect($output)
        ->toContain('for="settings-title"')
        ->toContain('id="settings-title"')
        ->toContain('name="settings[title]"');
});

it('balances namespace directive context after rendering', function () {
    $this->renderer->renderString(<<<'BLADE'
@craftNamespace('settings')
<input name="title">
@endCraftNamespace
BLADE);

    expect(Context::getHidden(NamespaceDirective::class))->toBe([]);
});
