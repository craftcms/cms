<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\User\Actions\SuspendUsers;
use CraftCms\Cms\User\Actions\UnsuspendUsers;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('unsuspends users via the Laravel perform-action route', function () {
    $user = UserModel::factory()->createElement(['admin' => false]);

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => User::class,
        'elementAction' => SuspendUsers::class,
        'elementIds' => [$user->id],
        'criteria' => ['status' => null],
    ])->assertOk();

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => User::class,
        'elementAction' => UnsuspendUsers::class,
        'elementIds' => [$user->id],
        'criteria' => ['status' => User::STATUS_SUSPENDED],
    ])->assertOk();

    expect(User::find()->id($user->id)->status(null)->one())
        ->not()->toBeNull();
});
