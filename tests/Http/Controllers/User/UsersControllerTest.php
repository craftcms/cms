<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Queries\UserQuery;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\UsersController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Edition::set(Edition::Pro);

    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(action([UsersController::class, 'index']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    get(action([UsersController::class, 'create']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    get(action([UsersController::class, 'edit']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    postJson(action([UsersController::class, 'destroy']))->assertUnauthorized();
});

test('index requires viewUsers', function () {
    get(action([UsersController::class, 'index']))->assertOk();

    Gate::before(function ($user, $ability) {
        if ($ability === 'viewUsers') {
            return false;
        }

        return null;
    });

    get(action([UsersController::class, 'index']))->assertForbidden();
});

test('create makes a user draft and redirects to it', function () {
    get(action([UsersController::class, 'create']))
        ->assertRedirect();
});

test('destroy deletes a user', function () {
    $user = \CraftCms\Cms\User\Models\User::factory()->create();

    expect(new UserQuery()->count())->toBe(2);

    postJson(action([UsersController::class, 'destroy']), [
        'userId' => $user->id,
    ])->assertOk();

    expect(new UserQuery()->count())->toBe(1);
});

test('edit shows a cp screen', function () {
    get(action([UsersController::class, 'edit']))
        ->assertSee(t('Profile'));
});

test('destroy validates required userId', function () {
    postJson(action([UsersController::class, 'destroy']), [])
        ->assertJsonValidationErrors(['userId']);
});

test('destroy can transfer content to another user', function () {
    $user = \CraftCms\Cms\User\Models\User::factory()->create();
    $transferTo = User::findOne();

    postJson(action([UsersController::class, 'destroy']), [
        'userId' => $user->id,
        'transferContentTo' => $transferTo->id,
    ])->assertOk();

    // Verify user was deleted
    expect(User::find()->id($user->id)->exists())->toBeFalse();
});

test('destroy handles non-existent user gracefully', function () {
    postJson(action([UsersController::class, 'destroy']), [
        'userId' => 99999,
    ])->assertStatus(400);
});

test('create redirects to edit screen for new user', function () {
    get(action([UsersController::class, 'create']))
        ->assertRedirectContains('users/');
});

test('edit can show specific user by ID', function () {
    get(action([UsersController::class, 'edit'], ['userId' => User::findOne()->id]))->assertOk();
});

test('index shows users list', function () {
    get(action([UsersController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Users'));
});

test('destroy requires proper authorization', function () {
    Gate::before(function ($user, $ability) {
        if ($ability === 'deleteUsers') {
            return false;
        }

        return null;
    });

    $user = \CraftCms\Cms\User\Models\User::factory()->create();

    postJson(action([UsersController::class, 'destroy']), [
        'userId' => $user->id,
    ])->assertForbidden();
});

test('create requires proper authorization', function () {
    Gate::before(function ($user, $ability) {
        if ($ability === 'registerUsers') {
            return false;
        }

        return null;
    });

    get(action([UsersController::class, 'create']))
        ->assertForbidden();
});
