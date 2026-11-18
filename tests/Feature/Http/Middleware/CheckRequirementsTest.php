<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('checks the requirements for each install path', function (int $cacheDuration) {
    Cms::config()->cacheDuration = $cacheDuration;
    $otherInstall = sprintf('requirementsChecked:%s', md5('/other/install/path'));
    $thisInstall = sprintf('requirementsChecked:%s', md5(base_path()));
    Cache::put($otherInstall, true);
    actingAs(User::find()->admin()->one());

    get(cp_url('settings'))->assertOk();

    expect(Cache::get($thisInstall))->toBeTrue()
        ->and(Cache::get($otherInstall))->toBeTrue();
})->with([
    'a cache duration' => 86400,
    'no cache expiry' => 0,
]);
