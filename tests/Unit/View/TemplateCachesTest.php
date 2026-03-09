<?php

declare(strict_types=1);

use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\Cms\View\Events\RegisterTemplateCacheCollectors;
use CraftCms\Cms\View\TemplateCaches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

final class TestTemplateCacheCollector implements CacheCollectorInterface
{
    public static array $calls = [];

    public static function key(): string
    {
        return 'test-collector';
    }

    public function begin(TemplateCacheContext $context): void
    {
        self::$calls[] = ['begin', $context->cacheKey];
    }

    public function end(TemplateCacheContext $context): array
    {
        self::$calls[] = ['end', $context->cacheKey];

        return ['applied' => true];
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void
    {
        self::$calls[] = ['apply', $context->cacheKey, $payload];
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

it('dispatches the collector registration event', function () {
    Event::fake(RegisterTemplateCacheCollectors::class);

    app(TemplateCaches::class)->startTemplateCache(global: true);

    Event::assertDispatched(RegisterTemplateCacheCollectors::class);
});

it('runs event-registered collectors', function () {
    Event::listen(RegisterTemplateCacheCollectors::class, function (RegisterTemplateCacheCollectors $event) {
        if ($event->types->doesntContain(TestTemplateCacheCollector::class)) {
            $event->types->add(TestTemplateCacheCollector::class);
        }
    });

    $service = app(TemplateCaches::class);

    $service->startTemplateCache(global: true);
    $service->endTemplateCache('collector-cache', true, null, null, 'cached body');
    $service->getTemplateCache('collector-cache', true);

    expect(collect(TestTemplateCacheCollector::$calls)->contains(
        fn (array $call) => $call === ['end', 'template::collector-cache::1'],
    ))->toBeTrue()
        ->and(collect(TestTemplateCacheCollector::$calls)->contains(
            fn (array $call) => $call[0] === 'apply' &&
                $call[1] === 'template::collector-cache::1' &&
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

    swapTemplateCacheRequest('/admin/news/p2');
    $service = app(TemplateCaches::class);
    $service->startTemplateCache(global: false);
    $service->endTemplateCache('scoped-cache', false, null, null, 'cp-page-two');

    swapTemplateCacheRequest('/admin/news');
    expect(app(TemplateCaches::class)->getTemplateCache('scoped-cache', false))->toBeNull();

    swapTemplateCacheRequest('/news/p2');
    expect(app(TemplateCaches::class)->getTemplateCache('scoped-cache', false))->toBeNull();

    swapTemplateCacheRequest('/admin/news/p2');
    expect(app(TemplateCaches::class)->getTemplateCache('scoped-cache', false))->toBe('cp-page-two');
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
