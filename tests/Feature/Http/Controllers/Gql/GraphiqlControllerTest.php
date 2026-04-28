<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Gql\GraphiqlController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication for the graphiql cp page', function () {
    Auth::logout();

    get(cp_url('graphiql'))->assertRedirect();
});

it('requires admin access for the graphiql cp page', function () {
    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    actingAs(User::findOne());

    get(cp_url('graphiql'))->assertForbidden();
});

it('allows graphiql without admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    get(action(GraphiqlController::class))->assertOk();
});

it('loads the graphiql page and rejects invalid schema uids', function () {
    get(action(GraphiqlController::class))
        ->assertOk()
        ->assertSee(t('Explore the GraphQL API'));

    get(action(GraphiqlController::class, ['schemaUid' => 'missing-schema']))
        ->assertBadRequest();
});
