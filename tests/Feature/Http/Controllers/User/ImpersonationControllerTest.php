<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Actions\GetImpersonationUrlAction;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('impersonates the user and redirects to the control panel via an impersonation URL', function () {
    $admin = User::find()->admin()->one();
    $target = UserModel::factory()->withPermissions(['accessCp'])->create(['admin' => false]);

    actingAs($admin);
    $url = app(GetImpersonationUrlAction::class)(User::find()->id($target->id)->one());

    get($url)->assertRedirect(Url::cpUrl('dashboard'));

    expect(auth()->id())->toBe($target->id);
});
