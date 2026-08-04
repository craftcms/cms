<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Http\Controllers\Gql\GraphiqlController;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication for the graphiql cp page', function () {
    Auth::logout();

    get(cp_url('graphql/explore'))->assertRedirect();
});

it('requires admin access for the graphiql cp page', function () {
    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    actingAs(User::findOne());

    get(cp_url('graphql/explore'))->assertForbidden();
});

it('allows graphiql without admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    get(action(GraphiqlController::class))->assertOk();
});

it('loads the graphiql inertia page and rejects invalid schema uids', function () {
    $schema = createSchemaForGraphiqlControllerTest();

    get(action(GraphiqlController::class))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/Explore')
            ->where('endpoint', Url::actionUrl('graphql/api'))
            ->where('selectedSchema.schema', '*')
            ->where('schemaOptions.0.value', '*')
            ->where('schemaOptions', fn ($schemaOptions) => collect($schemaOptions)
                ->pluck('value')
                ->contains($schema->uid)));

    get(action(GraphiqlController::class, ['schemaUid' => $schema->uid]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/Explore')
            ->where('selectedSchema.name', $schema->name)
            ->where('selectedSchema.schema', $schema->uid));

    get(action(GraphiqlController::class, ['schemaUid' => 'missing-schema']))
        ->assertBadRequest();
});

it('does not register the legacy graphiql cp route', function () {
    get(cp_url('graphiql'))->assertNotFound();
});

function createSchemaForGraphiqlControllerTest(): GqlSchema
{
    $schema = new GqlSchema([
        'name' => 'GraphiQL Schema '.bin2hex(random_bytes(4)),
        'scope' => GqlHelper::createFullAccessSchema()->scope,
    ]);

    expect(Gql::saveSchema($schema))->toBeTrue();

    return $schema;
}
