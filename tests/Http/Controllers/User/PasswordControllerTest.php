<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Users\PasswordController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(action([PasswordController::class, 'index']))->assertRedirect(Cms::config()->cpTrigger.'/login');
});

test('index', function () {
    get(action([PasswordController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Password'));
});

it('requires login for requireReset', function () {
    auth()->logout();

    postJson(action([PasswordController::class, 'requireReset']))->assertUnauthorized();
});

it('requires login for removeResetRequirement', function () {
    auth()->logout();

    postJson(action([PasswordController::class, 'removeResetRequirement']))->assertUnauthorized();
});

it('requires administrateUsers permission for requireReset and removeResetRequirement', function () {
    Gate::before(function ($user, $ability) {
        if ($ability === 'administrateUsers') {
            return false;
        }

        return null;
    });

    postJson(action([PasswordController::class, 'requireReset']), [
        'userId' => auth()->id(),
    ])->assertForbidden();

    postJson(action([PasswordController::class, 'removeResetRequirement']), [
        'userId' => auth()->id(),
    ])->assertForbidden();
});

it('validates userId exists for requireReset and removeResetRequirement', function (array $action) {
    postJson(action($action), ['userId' => 999999])
        ->assertJsonValidationErrorFor('userId');
})->with([
    [[PasswordController::class, 'requireReset']],
    [[PasswordController::class, 'removeResetRequirement']],
]);

it('validates userId is required for requireReset and removeResetRequirement', function (array $action) {
    postJson(action($action))->assertJsonValidationErrorFor('userId');
})->with([
    [[PasswordController::class, 'requireReset']],
    [[PasswordController::class, 'removeResetRequirement']],
]);

test('requireReset sets passwordResetRequired to true', function () {
    $user = User::findOne();
    $user->passwordResetRequired = false;
    Craft::$app->getElements()->saveElement($user, false);
    $userId = $user->id;

    postJson(action([PasswordController::class, 'requireReset']), [
        'userId' => $userId,
    ])->assertOk();

    expect(User::find()->id($userId)->status(null)->addSelect('passwordResetRequired')->first()->passwordResetRequired)->toBeTrue();
});

test('removeResetRequirement sets passwordResetRequired to false', function () {
    $user = User::findOne();
    $user->passwordResetRequired = true;
    Craft::$app->getElements()->saveElement($user, false);
    $userId = $user->id;

    postJson(action([PasswordController::class, 'removeResetRequirement']), [
        'userId' => $userId,
    ])->assertOk();

    expect(User::find()->id($userId)->status(null)->addSelect('passwordResetRequired')->first()->passwordResetRequired)->toBeFalse();
});
