<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\Cms\View\TemplateCacheCollectors;
use CraftCms\Cms\View\TemplateCaches;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;

class TestTemplateCacheCollector implements CacheCollectorInterface
{
    public static array $calls = [];

    public static function key(): string
    {
        return 'test-collector';
    }

    public function begin(TemplateCacheContext $context): void
    {
        static::$calls[] = ['begin', $context->cacheKey];
    }

    public function end(TemplateCacheContext $context): array
    {
        static::$calls[] = ['end', $context->cacheKey];

        return ['applied' => true];
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void
    {
        static::$calls[] = ['apply', $context->cacheKey, $payload];
    }
}

beforeEach(function () {
    Sites::setCurrentSite(new Site([
        'id' => 1,
        'language' => 'en-US',
        'baseUrl' => 'https://example.test/',
        'primary' => true,
        'hasUrls' => true,
    ]));
    TestTemplateCacheCollector::$calls = [];
});

it('runs registered collectors', function () {
    $registry = app(TemplateCacheCollectors::class);
    $registry->register(TestTemplateCacheCollector::class);

    $service = app(TemplateCaches::class);

    $service->startTemplateCache(global: true);
    $service->endTemplateCache('collector-cache', true, null, null, 'cached body');
    $service->getTemplateCache('collector-cache', true);

    expect(collect(TestTemplateCacheCollector::$calls)->pluck(0))->toContain('end', 'apply')
        ->and(collect(TestTemplateCacheCollector::$calls)->contains(
            fn (array $call) => $call[0] === 'apply' &&
                ($call[2] ?? null) === ['applied' => true],
        ))->toBeTrue();
});

it('applies cached dependency info on cache hits', function () {
    Cache::put('template::dependency-cache::1', [
        'body' => 'cached body',
        'cacheInfo' => [
            'tags' => ['cached-tag'],
            'expiryDate' => null,
        ],
        'collectors' => [],
    ]);

    $dependencyCollector = app(DependencyCollector::class);
    $dependencyCollector->begin(new TemplateCacheContext(
        cacheKey: 'outer-cache',
        global: true,
        resources: false,
    ));

    $body = app(TemplateCaches::class)->getTemplateCache('dependency-cache', true);
    [$dependency] = $dependencyCollector->stop();

    expect($body)->toBe('cached body')
        ->and($dependency?->tags)->toContain('cached-tag');
});

it('scopes non-global caches by request type and page number', function () {
    setTemplateCacheConsoleState(false);

    swapTemplateCacheRequest('/admin/news?page=2');
    $service = app(TemplateCaches::class);
    $service->startTemplateCache(global: false);
    $service->endTemplateCache('scoped-cache', false, null, null, 'cp-page-two');

    swapTemplateCacheRequest('/admin/news');
    expect(app(TemplateCaches::class)->getTemplateCache('scoped-cache', false))->toBeNull();

    swapTemplateCacheRequest('/news?page=2');
    expect(app(TemplateCaches::class)->getTemplateCache('scoped-cache', false))->toBeNull();

    swapTemplateCacheRequest('/admin/news?page=2');
    expect(app(TemplateCaches::class)->getTemplateCache('scoped-cache', false))->toBe('cp-page-two');
});

it('scopes non-global caches using the paginator current page resolver', function () {
    setTemplateCacheConsoleState(false);
    swapTemplateCacheRequest('/admin/news?page=2');
    Paginator::currentPageResolver(fn () => 3);

    $service = app(TemplateCaches::class);
    $service->startTemplateCache(global: false);
    $service->endTemplateCache('resolved-page-cache', false, null, null, 'cp-page-three');

    swapTemplateCacheRequest('/admin/news?page=3');
    expect(app(TemplateCaches::class)->getTemplateCache('resolved-page-cache', false))->toBe('cp-page-three');

    swapTemplateCacheRequest('/admin/news?page=2');
    expect(app(TemplateCaches::class)->getTemplateCache('resolved-page-cache', false))->toBe('cp-page-three');
});

it('does not read or write template caches when the original request had a token', function () {
    setTemplateCacheConsoleState(false);
    swapTemplateCacheRequest('/news');
    Context::addHidden(HandleTokenRequest::HAD_TOKEN_KEY, true);

    $service = app(TemplateCaches::class);

    $service->startTemplateCache(global: false);
    $service->endTemplateCache('preview-cache', false, null, null, 'preview body');

    expect($service->getTemplateCache('preview-cache', false))->toBeNull();
});

function swapTemplateCacheRequest(string $uri): void
{
    app()->instance('request', Request::create($uri));
    app()->forgetScopedInstances();
}

function setTemplateCacheConsoleState(bool $runningInConsole): void
{
    new ReflectionProperty(app(), 'isRunningInConsole')->setValue(app(), $runningInConsole);
}
