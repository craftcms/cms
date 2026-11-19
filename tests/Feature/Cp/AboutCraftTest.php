<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\AboutCraft;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

function aboutCraft(?array $trial, int $updates): AboutCraft
{
    mock(License::class)->allows('trialInfo')->andReturn($trial);
    mock(Updates::class)->allows('totalAvailableUpdates')->andReturn($updates);

    return app(AboutCraft::class);
}

it('needs attention while there are trials to buy', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement());

    expect(aboutCraft(['message' => 'Craft Pro is installed as a trial.', 'cartUrl' => '#'], 0)->needsAttention())
        ->toBeTrue();
});

it('needs attention while there are updates, for users who can update Craft', function () {
    actingAs(User::find()->one());

    expect(aboutCraft(null, 2)->needsAttention())->toBeTrue();
});

it('doesn’t need attention for updates that the user can’t perform', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement());

    expect(aboutCraft(null, 2)->needsAttention())->toBeFalse();
});

it('doesn’t need attention without trials or updates', function () {
    actingAs(User::find()->one());

    expect(aboutCraft(null, 0)->needsAttention())->toBeFalse();
});
