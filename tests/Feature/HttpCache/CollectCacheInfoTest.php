<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\EntryFactory;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\ResponseCacheInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\renderString;

it('tags a page with the elements it queried and caps its lifetimes at the earliest expiry', function () {
    $this->freezeSecond();
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);

    Route::get('/page', function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        return response('ok')->setPublic()->setMaxAge(3600)->setSharedMaxAge(86400)->setExpires(now()->addHour());
    });

    $response = $this->get('/page');

    expect(ResponseCacheInfo::getTags())->toContain('element', 'element::'.Entry::class)
        ->and($response->headers->getCacheControlDirective('max-age'))->toBe('600')
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe('600')
        ->and($response->getExpires()->getTimestamp())->toBe(now()->addMinutes(10)->getTimestamp());
});

it('lets cache drivers in route middleware read element tags and the earliest expiry', function () {
    $this->freezeSecond();
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);
    $seen = [];
    app()->instance('test.seen', function (array $info) use (&$seen) {
        $seen = $info;
    });

    Route::get('/page', function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        return response('ok');
    })->middleware(RecordCacheInfo::class);

    $this->get('/page');

    expect($seen[0])->toContain('element', 'element::'.Entry::class)
        ->and($seen[1])->toBe(now()->addMinutes(10)->getTimestamp());
});

class RecordCacheInfo
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        app('test.seen')([ResponseCacheInfo::getTags(), ResponseCacheInfo::getExpiryDate()?->getTimestamp()]);

        return $response;
    }
}

it('keeps a private response private when capping its lifetimes', function () {
    $this->freezeSecond();
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);

    Route::get('/page', function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        $response = response('ok')->setPrivate()->setMaxAge(3600);
        $response->headers->addCacheControlDirective('s-maxage', '86400');

        return $response;
    });

    $response = $this->get('/page');

    expect($response->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('public'))->toBeFalse()
        ->and($response->headers->getCacheControlDirective('max-age'))->toBe('600')
        ->and($response->headers->getCacheControlDirective('s-maxage'))->toBe('600');
});

it('caps lifetimes at expiry dates added through the facade', function () {
    $this->freezeSecond();

    Route::get('/page', function () {
        ResponseCacheInfo::addExpiryDate(now()->addMinutes(5));

        return response('ok')->setPublic()->setMaxAge(3600);
    });

    expect($this->get('/page')->headers->getCacheControlDirective('max-age'))->toBe('300');
});

it('caps the lifetimes of 304 responses', function () {
    $this->freezeSecond();
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);

    Route::get('/page', function (Request $request) use ($entry) {
        Entry::find()->id($entry->id)->one();

        $response = response('ok')->setPublic()->setMaxAge(3600)->setEtag('abc');
        $response->isNotModified($request);

        return $response;
    });

    $response = $this->get('/page', ['If-None-Match' => '"abc"']);

    expect($response->getStatusCode())->toBe(304)
        ->and($response->headers->getCacheControlDirective('max-age'))->toBe('600');
});

it('leaves the lifetimes of error responses alone', function () {
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);

    Route::get('/page', function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        return response('gone', 410)->setPublic()->setMaxAge(3600);
    });

    expect($this->get('/page')->headers->getCacheControlDirective('max-age'))->toBe('3600');
});

it('leaves lifetimes shorter than the earliest expiry alone', function () {
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addDay()]);

    Route::get('/page', function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        return response('ok')->setPublic()->setMaxAge(60);
    });

    expect($this->get('/page')->headers->getCacheControlDirective('max-age'))->toBe('60');
});

it('collects tags added through the facade and the Twig global', function () {
    Route::get('/page', function () {
        ResponseCacheInfo::addTags(['from-php']);

        return response(renderString("{% do ResponseCacheInfo.addTags(['from-twig']) %}ok"));
    });

    $this->get('/page');

    expect(ResponseCacheInfo::getTags())->toBe(['from-php', 'from-twig']);
});

it('keeps tags and element expiry dates from inside a cached block on a cache hit', function () {
    $this->freezeSecond();
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);

    Route::get('/page', fn () => response(renderString(
        "{% cache globally using key 'nav' %}{% do ResponseCacheInfo.addTags(['nav']) %}"
        ."{{ craft.entries.id({$entry->id}).one().id }}-{{ random() }}{% endcache %}",
    ))->setPublic()->setMaxAge(3600));

    $first = $this->get('/page');
    expect(ResponseCacheInfo::getTags())->toContain('nav', 'element::'.Entry::class);

    app()->forgetScopedInstances();
    $second = $this->get('/page');

    expect($second->getContent())->toBe($first->getContent())
        ->and(ResponseCacheInfo::getTags())->toContain('nav', 'element::'.Entry::class)
        ->and(ResponseCacheInfo::getExpiryDate()?->getTimestamp())->toBe(now()->addMinutes(10)->getTimestamp())
        ->and($second->headers->getCacheControlDirective('max-age'))->toBe('600');
});

it('clears cached blocks that added a tag when the tag is invalidated', function () {
    Event::fake([ElementCachesInvalidated::class]);
    Route::get('/page', fn () => response(renderString(
        "{% cache globally using key 'nav' %}{% do ResponseCacheInfo.addTags(['nav']) %}{{ random() }}{% endcache %}",
    )));

    $first = $this->get('/page')->getContent();
    app()->forgetScopedInstances();
    expect($this->get('/page')->getContent())->toBe($first);

    expect(app(ElementCaches::class)->invalidateTags(['nav', '']))->toBe(['nav']);
    Event::assertDispatched(ElementCachesInvalidated::class, fn (ElementCachesInvalidated $event) => $event->tags === ['nav']);

    app()->forgetScopedInstances();
    expect($this->get('/page')->getContent())->not->toBe($first);
});

it('only collects element tags for eligible requests', function (string $method, string $uri) {
    $uri = $uri === 'cp' ? '/'.Cms::config()->cpTrigger.'/page' : $uri;
    $entry = EntryFactory::new()->create();

    Route::match(['GET', 'POST'], $uri, function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        return response(renderString("{% cache globally using key 'block' %}{{ craft.entries.id({$entry->id}).one().id }}{% endcache %}"));
    });

    // The second request replays the cached block.
    $this->call($method, $uri)->assertOk();
    $this->call($method, $uri)->assertOk();

    expect(ResponseCacheInfo::getTags())->toBe([])
        ->and(ResponseCacheInfo::getExpiryDate())->toBeNull();
})->with([
    'POST' => ['POST', '/page'],
    'control panel' => ['GET', 'cp'],
    'action' => ['GET', '/actions/foo/bar'],
]);
