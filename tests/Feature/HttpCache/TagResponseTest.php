<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\EntryFactory;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\ResponseTagger;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\renderString;

beforeEach(function () {
    config([
        'craft.http-cache.tags.formatter' => CommaSeparatedTagHeaderFormatter::class,
        'craft.http-cache.tags.formatter_options' => ['headerName' => 'Cache-Tag'],
    ]);
});

it('tags a page with the elements it queried and caps its lifetimes at the earliest expiry', function () {
    $this->freezeSecond();
    $entry = EntryFactory::new()->create(['expiryDate' => now()->addMinutes(10)]);

    Route::get('/page', function () use ($entry) {
        Entry::find()->id($entry->id)->one();

        return response('ok')->setPublic()->setMaxAge(3600)->setSharedMaxAge(86400)->setExpires(now()->addHour());
    });

    $response = $this->get('/page');

    $tags = explode(',', $response->headers->get('Cache-Tag'));
    expect($tags)->toContain('element', 'element::'.Entry::class)
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

it('sends tags added through the facade and the Twig global', function () {
    Route::get('/page', function () {
        ResponseTagger::addTags(['from-php']);

        return response(renderString("{% do ResponseTagger.addTags(['from-twig']) %}ok"));
    });

    expect($this->get('/page')->headers->get('Cache-Tag'))->toBe('from-php,from-twig');
});

it('keeps tags added inside a cached block on a cache hit', function () {
    Route::get('/page', fn () => response(renderString(
        "{% cache globally using key 'nav' %}{% do ResponseTagger.addTags(['nav']) %}nav{% endcache %}",
    )));

    expect($this->get('/page')->headers->get('Cache-Tag'))->toBe('nav');

    app()->forgetScopedInstances();
    expect(explode(',', $this->get('/page')->headers->get('Cache-Tag')))->toContain('nav');
});

it('only tags eligible responses', function (string $method, string $uri, int $status) {
    $uri = $uri === 'cp' ? '/'.Cms::config()->cpTrigger.'/page' : $uri;

    Route::match(['GET', 'HEAD', 'POST'], $uri, function () use ($status) {
        ResponseTagger::addTags(['page']);

        return response('ok', $status);
    });

    expect($this->call($method, $uri)->headers->has('Cache-Tag'))->toBeFalse();
})->with([
    'POST' => ['POST', '/page', 200],
    'control panel' => ['GET', 'cp', 200],
    'action' => ['GET', '/actions/foo/bar', 200],
    'not found' => ['GET', '/page', 404],
    'server error' => ['GET', '/page', 500],
]);

it('sends no tag header without a formatter', function () {
    config(['craft.http-cache.tags.formatter' => null]);

    Route::get('/page', function () {
        ResponseTagger::addTags(['page']);

        return response('ok');
    });

    expect($this->get('/page')->headers->has('Cache-Tag'))->toBeFalse()
        ->and(ResponseTagger::getTags())->toBe(['page']);
});
