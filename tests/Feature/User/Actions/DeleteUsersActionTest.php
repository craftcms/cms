<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\User\Actions\DeleteUsers;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('deletes users via the Laravel perform-action route', function () {
    $user = UserModel::factory()->createElement(['admin' => false]);

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => User::class,
        'elementAction' => DeleteUsers::class,
        'elementIds' => [$user->id],
        'criteria' => ['status' => null],
    ])->assertOk();

    expect(DB::table(Table::ELEMENTS)->where('id', $user->id)->value('dateDeleted'))
        ->not()->toBeNull();
});
