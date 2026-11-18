<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\App\AboutCraftController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

it('returns Craft’s version, edition, trials, and updates', function () {
    actingAs(User::find()->one());

    getJson(action(AboutCraftController::class))
        ->assertJsonPath('version', Cms::VERSION)
        ->assertJsonPath('edition', Edition::get()->name)
        ->assertJsonPath('trial', null)
        ->assertJsonStructure(['updates' => ['cached', 'total', 'dateFetched']]);
});

it('leaves out updates for users who can’t update Craft', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement());

    getJson(action(AboutCraftController::class))->assertJsonPath('updates', null);
});
