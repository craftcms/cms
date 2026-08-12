<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\UsersController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
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

    get(action([UsersController::class, 'create']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    get(action([UsersController::class, 'edit']))->assertRedirect(Cms::config()->cpTrigger.'/login');
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

    // The draft has no email or username yet, which the editor's metadata has
    // to survive.
    test('the new draft’s edit screen renders', function () {
        $redirect = get(action([UsersController::class, 'create']))->headers->get('Location');

        get($redirect)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('users/Edit')
                ->has('metadataHtml'));
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

    // `action()` resolves this controller action to `myaccount`, which is
    // registered first, so another user's screen has to be addressed by URL.
    test('edit can show specific user by ID', function () {
        get(cp_url('users/'.User::findOne()->id))->assertOk();
    });

    test('edit renders the Inertia profile page for the current user', function () {
        $user = currentUser();

        get(action([UsersController::class, 'edit']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('users/Edit')
                ->where('userId', $user->getCraftUserId())
                ->where('elementType', User::class)
                ->where('title', t('My Account'))
                ->where('docTitle', t('Profile'))
                ->has('crumbs', 2)
                ->where('crumbs.0.label', t('Users'))
                ->has('form.nodes')
                ->has('subnav'));
    });

    test('edit renders the same Inertia page for other users', function () {
        $other = UserFactory::new()->createElement();

        get(cp_url("users/$other->id"))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('users/Edit')
                ->where('userId', $other->id)
                ->where('title', $other->getUiLabel()));
    });

    test('edit posts to the user save action', function () {
        get(action([UsersController::class, 'edit']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('saveUrl', fn (string $url): bool => str_contains($url, 'users/save-user'))
                // A user has no drafts to autosave into.
                ->where('canAutosave', false)
                // Their status follows from the account actions, not a switch.
                ->where('sidebarForm', null));
    });
});
