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

it('caches captured Blade output when cache if condition is true', function () {
    $first = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheIf($enabled, "blade-cache-if-true", global: true)
First
@endCraftCache
BLADE, ['enabled' => true]));
    $second = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheIf($enabled, "blade-cache-if-true", global: true)
Second
@endCraftCache
BLADE, ['enabled' => true]));

    expect($first)->toBe('First')
        ->and($second)->toBe('First');
});

it('renders dynamic Blade output when cache if condition is false', function () {
    $first = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheIf($enabled, "blade-cache-if-false", global: true)
First
@endCraftCache
BLADE, ['enabled' => false]));
    $second = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheIf($enabled, "blade-cache-if-false", global: true)
Second
@endCraftCache
BLADE, ['enabled' => false]));

    expect($first)->toBe('First')
        ->and($second)->toBe('Second');
});

it('caches captured Blade output when cache unless condition is false', function () {
    $first = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheUnless($disabled, "blade-cache-unless-false", global: true)
First
@endCraftCache
BLADE, ['disabled' => false]));
    $second = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheUnless($disabled, "blade-cache-unless-false", global: true)
Second
@endCraftCache
BLADE, ['disabled' => false]));

    expect($first)->toBe('First')
        ->and($second)->toBe('First');
});

it('renders dynamic Blade output when cache unless condition is true', function () {
    $first = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheUnless($disabled, "blade-cache-unless-true", global: true)
First
@endCraftCache
BLADE, ['disabled' => true]));
    $second = trim((string) $this->renderer->renderString(<<<'BLADE'
@craftCacheUnless($disabled, "blade-cache-unless-true", global: true)
Second
@endCraftCache
BLADE, ['disabled' => true]));

    expect($first)->toBe('First')
        ->and($second)->toBe('Second');
});

it('balances cache directive context after rendering', function () {
    $this->renderer->renderString(<<<'BLADE'
@craftCache("blade-cache-context", global: true)
Cached
@endCraftCache
BLADE);

    expect(Context::getHidden(CacheDirective::class))->toBe([]);
});
