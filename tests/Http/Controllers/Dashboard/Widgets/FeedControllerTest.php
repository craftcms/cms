<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('requires login', function () {
    postJson(action([FeedController::class, 'cacheData']))
        ->assertUnauthorized();
});

it('caches data under the key of the url', function () {
    actingAs(User::first());

    expect(Cache::has('feed:https://craftcms.com/news.rss'))->toBeFalse();

    postJson(action([FeedController::class, 'cacheData']), [
        'url' => 'https://craftcms.com/news.rss',
        'data' => 'just some data',
    ])->assertOk();

    expect(Cache::get('feed:https://craftcms.com/news.rss'))->toBe('just some data');
});

it('requires a valid url', function () {
    actingAs(User::first());

    expect(Cache::has('feed:not-an-url'))->toBeFalse();

    postJson(action([FeedController::class, 'cacheData']), [
        'url' => 'not-an-url',
        'data' => 'just some data',
    ])->assertJsonValidationErrorFor('url');

    expect(Cache::has('feed:not-an-url'))->toBeFalse();
});
