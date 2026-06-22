<?php

declare(strict_types=1);

use CraftCms\Cms\View\BladeDirectives\CacheDirective;
use CraftCms\Cms\View\BladeRenderer;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

it('caches captured Blade output', function () {
    $first = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCache("blade-cache", global: true)
First
@endCraftCache
BLADE));
    $second = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCache("blade-cache", global: true)
Second
@endCraftCache
BLADE));

    expect($first)->toBe('First')
        ->and($second)->toBe('First')
        ->and(Context::getHidden(CacheDirective::class))->toBe([]);
});

it('balances cache directive context after rendering', function () {
    $this->renderer->renderString(<<<'BLADE'
@craftCache("blade-cache-context", global: true)
Cached
@endCraftCache
BLADE);

    expect(Context::getHidden(CacheDirective::class))->toBe([]);
});
