<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Edition::set(Edition::Pro);

    actingAs(User::findOne());

    $this->cpTrigger = Cms::config()->cpTrigger;
});

it('requires login', function () {
    auth()->logout();

    get("/{$this->cpTrigger}/users")
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('requires the viewUsers permission', function () {
    get("/{$this->cpTrigger}/users")->assertOk();

    Gate::before(fn ($user, $ability) => $ability === 'viewUsers' ? false : null);

    get("/{$this->cpTrigger}/users")->assertForbidden();
});

it('returns an Inertia response with elements and pagination', function () {
    get("/{$this->cpTrigger}/users")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Index')
            ->has('data')
            ->has('pagination')
            ->has('sort')
            ->has('sources')
        );
});

it('defaults to the “all users” source', function () {
    get("/{$this->cpTrigger}/users")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('slug', null)
            ->where('source.key', '*')
        );
});

it('selects the source matching the slug in the path', function () {
    get("/{$this->cpTrigger}/users/admins")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            // The raw slug echoes back so client reloads stay on this source;
            // the resolved key drives which source is active.
            ->where('slug', 'admins')
            ->where('source.key', 'admins')
        );
});

it('selects a user group source by its handle', function () {
    $group = UserGroup::factory()->create(['handle' => 'authors']);

    get("/{$this->cpTrigger}/users/authors")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('source.key', "group:{$group->uid}")
        );
});

it('scopes the list to the selected group', function () {
    $group = UserGroup::factory()->create();

    $user = UserFactory::new()->createElement();
    app(Users::class)->assignUserToGroups($user->id, [$group->id]);

    UserFactory::new()->createElement();

    get("/{$this->cpTrigger}/users?".http_build_query([
        'source' => "group:{$group->uid}",
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
        );
});

it('falls back to the default source for an unknown slug', function () {
    get("/{$this->cpTrigger}/users/not-a-real-group")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('source.key', '*')
        );
});

it('paginates users via query params', function () {
    foreach (range(1, 4) as $ignored) {
        UserFactory::new()->createElement();
    }

    get("/{$this->cpTrigger}/users?".http_build_query(['per_page' => 2]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.per_page', 2)
            ->count('data', 2)
        );
});

it('exposes whether the current user can register users', function () {
    get("/{$this->cpTrigger}/users")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canRegisterUsers', true)
            ->has('newUserLabel')
        );
});

it('404s on Solo', function () {
    // WrongEditionException only renders its 404 outside debug mode.
    config()->set('app.debug', false);

    Edition::set(Edition::Solo);

    get("/{$this->cpTrigger}/users")->assertNotFound();
});
