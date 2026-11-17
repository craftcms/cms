<?php

declare(strict_types=1);

use CraftCms\Cms\License\License;
use CraftCms\Cms\Update\Data\Updates as UpdatesData;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Cache\Events\RetrievingManyKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('reads each cache key at most once per control panel request', function () {
    Cache::put(License::CACHE_KEY_LICENSE_INFO, [
        'craft' => ['id' => 1, 'edition' => 'pro', 'status' => 'valid'],
        'plugin-test-plugin' => ['id' => 2, 'edition' => 'standard', 'status' => 'valid'],
    ]);
    Cache::put(Updates::class, new UpdatesData);
    Cache::put(sprintf('editionTestableDomain@%s', parse_url(cp_url(), PHP_URL_HOST)), 0);
    actingAs(User::find()->admin()->one());

    $reads = [];
    Event::listen([RetrievingKey::class, RetrievingManyKeys::class], function (RetrievingKey|RetrievingManyKeys $event) use (&$reads) {
        foreach ($event instanceof RetrievingKey ? [$event->key] : $event->keys as $key) {
            $reads[] = $key;
        }
    });

    get(cp_url('settings'))->assertOk();

    expect($reads)->toContain(License::CACHE_KEY_LICENSE_INFO, Updates::class)
        ->and(array_filter(array_count_values($reads), fn (int $count) => $count > 1))->toBe([]);
});
