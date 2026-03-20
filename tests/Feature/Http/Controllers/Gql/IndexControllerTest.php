<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Gql\IndexController;
use CraftCms\Cms\Support\URL;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication for the graphql cp index', function () {
    Auth::logout();

    get(cp_url('graphql'))->assertRedirect();
});

it('requires admin access for the graphql cp index', function () {
    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    actingAs(User::findOne());

    get(cp_url('graphql'))->assertForbidden();
});

it('redirects the graphql cp index to schemas when admin changes are allowed', function () {
    Cms::config()->allowAdminChanges = true;

    get(action(IndexController::class))
        ->assertRedirect(URL::cpUrl('graphql/schemas'));
});

it('redirects the graphql cp index to tokens when admin changes are disabled', function () {
    Cms::config()->allowAdminChanges = false;

    get(action(IndexController::class))
        ->assertRedirect(URL::cpUrl('graphql/tokens'));
});
