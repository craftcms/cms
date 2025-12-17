<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\Controllers\Users\UnlockController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    postJson(action(UnlockController::class))->assertUnauthorized();
});

test('it unlocks a user', function () {
    DB::table(Table::USERS)->update(['locked' => true]);

    expect((bool) DB::table(Table::USERS)->first()->locked)->toBeTrue();

    postJson(action(UnlockController::class), [
        'userId' => auth()->id(),
    ])->assertOk();

    expect((bool) DB::table(Table::USERS)->first()->locked)->toBeFalse();
});

it('requires moderateUsers permission', function () {
    \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
        if ($ability === 'moderateUsers') {
            return false;
        }

        return null;
    });

    postJson(action(UnlockController::class), [
        'userId' => auth()->id(),
    ])->assertForbidden();
});
