<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\UsersController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Edition::set(Edition::Pro);

    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(action([UsersController::class, 'index']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    get(action([UsersController::class, 'create']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    get(action([UsersController::class, 'edit']))->assertRedirect(Cms::config()->cpTrigger.'/login');
});

describe('index', function () {
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

    test('index shows users list', function () {
        get(action([UsersController::class, 'index']))
            ->assertOk()
            ->assertSee(t('Users'));
    });
});

describe('create', function () {
    test('create makes a user draft and redirects to it', function () {
        get(action([UsersController::class, 'create']))
            ->assertRedirect();
    });

    test('create redirects to edit screen for new user', function () {
        get(action([UsersController::class, 'create']))
            ->assertRedirectContains('users/');
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
});

describe('edit', function () {
    test('edit shows a cp screen', function () {
        get(action([UsersController::class, 'edit']))
            ->assertSee(t('Profile'));
    });

    test('edit can show specific user by ID', function () {
        get(action([UsersController::class, 'edit'], ['userId' => User::findOne()->id]))->assertOk();
    });

    test('edit renders the Inertia profile page for the current user', function () {
        $user = currentUser();

        get(action([UsersController::class, 'edit']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('users/Profile')
                ->where('userId', $user->getCraftUserId())
                ->where('email', $user->email)
                ->where('title', t('My Account'))
                ->has('crumbs', 2)
                ->where('crumbs.0.label', t('Users'))
                ->has('tabMenu')
                ->has('subnav')
                ->where('formFragment.html', fn (string $html): bool => str_contains($html, 'data-layout-tab')));
    });

    test('edit renders the legacy element editor for other users', function () {
        $other = UserFactory::new()->createElement();

        get(action([UsersController::class, 'edit'], ['userId' => $other->id]))
            ->assertOk()
            ->assertDontSee('users/Profile');
    });
});
