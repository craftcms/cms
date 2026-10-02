<?php

use CraftCms\Cms\Http\Controllers\Users\SuspendController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    postJson(action([SuspendController::class, 'suspend']))->assertUnauthorized();
    postJson(action([SuspendController::class, 'unsuspend']))->assertUnauthorized();
});

test('suspend & unsuspend', function () {
    expect(User::find()->status(null)->first()->suspended)->toBeFalse();

    postJson(action([SuspendController::class, 'suspend']), [
        'userId' => auth()->id(),
    ])->assertOk();

    expect(User::find()->status(null)->first()->suspended)->toBeTrue();

    postJson(action([SuspendController::class, 'unsuspend']), [
        'userId' => auth()->id(),
    ])->assertOk();

    expect(User::find()->status(null)->first()->suspended)->toBeFalse();
});

it('requires moderateUsers permission', function () {
    Gate::before(function ($user, $ability) {
        if ($ability === 'moderateUsers') {
            return false;
        }

        return null;
    });

    postJson(action([SuspendController::class, 'suspend'], [
        'userId' => auth()->id(),
    ]))->assertForbidden();

    postJson(action([SuspendController::class, 'unsuspend'], [
        'userId' => auth()->id(),
    ]))->assertForbidden();
});
