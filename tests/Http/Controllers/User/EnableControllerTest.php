<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\Controllers\Users\EnableController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    postJson(action(EnableController::class))->assertUnauthorized();
});

test('it enables a user', function () {
    DB::table(Table::ELEMENTS)->where('id', auth()->id())->update(['enabled' => false]);

    expect(User::find()->status(null)->first()->enabled)->toBeFalse();

    postJson(action(EnableController::class), [
        'userId' => auth()->id(),
    ])->assertOk();

    expect(User::find()->status(null)->first()->enabled)->toBeTrue();
});
