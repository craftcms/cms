<?php

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

    get(action([UsersController::class, 'index']))->assertRedirect('admin/login');
    get(action([UsersController::class, 'create']))->assertRedirect('admin/login');
    get(action([UsersController::class, 'edit']))->assertRedirect('admin/login');
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

test('edit shows a cp screen', function () {
    get(action([UsersController::class, 'edit']))
        ->assertSee(t('Profile'));
});

test('destroy deletes a user', function () {
    expect(new UserQuery()->count())->toBe(1);

    postJson(action([UsersController::class, 'destroy']), [
        'userId' => auth()->id(),
    ])->assertOk();

    expect(new UserQuery()->count())->toBe(0);
});
