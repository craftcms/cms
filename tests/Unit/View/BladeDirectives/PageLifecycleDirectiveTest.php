<?php

declare(strict_types=1);

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateMode;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

it('renders page lifecycle placeholders', function () {
    $output = $this->renderer->renderString(<<<'BLADE'
@craftHead
@craftBeginBody
@craftEndBody
BLADE);

    expect($output)
        ->toContain(PageLifecycle::HEAD_PLACEHOLDER)
        ->toContain(PageLifecycle::BODY_BEGIN_PLACEHOLDER)
        ->toContain(PageLifecycle::BODY_END_PLACEHOLDER);
});
