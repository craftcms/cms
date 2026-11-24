<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\EntryFactory;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\ResponseTagger;
use Illuminate\Http\Request;
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

    expect(ResponseTagger::getTags())->toContain('element', 'element::'.Entry::class)
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

        app('test.seen')([ResponseTagger::getTags(), ResponseTagger::getExpiryDate()?->getTimestamp()]);

        return $response;
    }
}

it('keeps a private response private when capping its lifetimes', function () {
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);

    Route::get('/page', function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        $response = response('ok')->setPrivate()->setMaxAge(3600);
        $response->headers->addCacheControlDirective('s-maxage', '86400');

        return $response;
    });

    $response = $this->get('/page');

    expect($response->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('public'))->toBeFalse();
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
        ResponseTagger::addTags(['from-php']);

        return response(renderString("{% do ResponseTagger.addTags(['from-twig']) %}ok"));
    });

    $this->get('/page');

    expect(ResponseTagger::getTags())->toBe(['from-php', 'from-twig']);
});

it('keeps tags added inside a cached block on a cache hit', function () {
    Route::get('/page', fn () => response(renderString(
        "{% cache globally using key 'nav' %}{% do ResponseTagger.addTags(['nav']) %}nav{% endcache %}",
    )));

    $this->get('/page');
    expect(ResponseTagger::getTags())->toBe(['nav']);

    app()->forgetScopedInstances();
    $this->get('/page');
    expect(ResponseTagger::getTags())->toContain('nav');
});

it('only collects element tags for eligible requests', function (string $method, string $uri) {
    $uri = $uri === 'cp' ? '/'.Cms::config()->cpTrigger.'/page' : $uri;
    $entry = EntryFactory::new()->create();

    Route::match(['GET', 'POST'], $uri, function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        return response('ok');
    });

    $this->call($method, $uri);

    expect(ResponseTagger::getTags())->toBe([]);
})->with([
    'POST' => ['POST', '/page'],
    'control panel' => ['GET', 'cp'],
    'action' => ['GET', '/actions/foo/bar'],
]);
