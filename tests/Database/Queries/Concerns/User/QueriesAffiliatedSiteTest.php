<?php

use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;

it('can query users by affiliated site', function (mixed $param, int $expectedCount) {
    $extraSite = Site::factory()->create(['handle' => 'extraSite']);

    Sites::refreshSites();

    User::first()->update(['affiliatedSiteId' => Site::first()->id]);
    User::factory()->create(['affiliatedSiteId' => $extraSite->id]);

    expect(userQuery()->affiliatedSite($param)->count())->toBe($expectedCount);
})->with([
    [null, 2],
    ['defaultSite', 1],
    [['not', 'defaultSite'], 1],
    [['extraSite', 'defaultSite'], 2],
    ['*', 2],
]);
