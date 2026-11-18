<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('checks the requirements again when the install has moved', function (int $cacheDuration) {
    Cms::config()->cacheDuration = $cacheDuration;
    Cache::put('basePath', '/old/install/path');
    actingAs(User::find()->admin()->one());

    get(cp_url('settings'))->assertOk();

    expect(Cache::get('basePath'))->toBe(base_path());
})->with([
    'a cache duration' => 86400,
    'no cache expiry' => 0,
]);
