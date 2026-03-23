<?php

declare(strict_types=1);

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Http\Controllers\Gql\SchemasController;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    Session::passwordConfirmed();
});

it('requires authentication for schema pages', function () {
    $schema = createSchemaForSchemasControllerTest();

    Auth::logout();

    get(cp_url('graphql/schemas'))->assertRedirect();
    get(cp_url('graphql/schemas/new'))->assertRedirect();
    get(cp_url('graphql/schemas/public'))->assertRedirect();
    get(action([SchemasController::class, 'edit'], ['schemaId' => $schema->id]))->assertRedirect();
});

it('requires admin access for schema pages and actions', function () {
    $schema = createSchemaForSchemasControllerTest();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    actingAs(User::findOne());

    get(cp_url('graphql/schemas'))->assertForbidden();
    get(cp_url('graphql/schemas/new'))->assertForbidden();
    get(cp_url('graphql/schemas/public'))->assertForbidden();
    get(action([SchemasController::class, 'edit'], ['schemaId' => $schema->id]))->assertForbidden();

    postJson(cp_url('actions/graphql/save-schema'), [
        'name' => 'Protected Schema',
        'permissions' => schemaControllerScope(),
    ])->assertForbidden();

    postJson(cp_url('actions/graphql/save-public-schema'), [
        'permissions' => schemaControllerScope(),
        'enabled' => true,
    ])->assertForbidden();

    postJson(cp_url('actions/graphql/delete-schema'), [
        'id' => $schema->id,
    ])->assertForbidden();
});

it('forbids schema pages when admin changes are disabled', function () {
    $schema = createSchemaForSchemasControllerTest();
    Cms::config()->allowAdminChanges = false;

    get(action([SchemasController::class, 'index']))->assertForbidden();
    get(action([SchemasController::class, 'create']))->assertForbidden();
    get(action([SchemasController::class, 'edit'], ['schemaId' => $schema->id]))->assertForbidden();
    get(action([SchemasController::class, 'editPublic']))->assertForbidden();
});

it('renders the schema index, create, edit, and public edit screens', function () {
    $schema = createSchemaForSchemasControllerTest();

    get(action([SchemasController::class, 'index']))
        ->assertOk()
        ->assertViewIs('graphql.schemas._index');

    get(action([SchemasController::class, 'create']))
        ->assertOk()
        ->assertViewIs('graphql.schemas._edit')
        ->assertViewHas('schema')
        ->assertViewHas('title', t('Create a new GraphQL Schema'));

    get(action([SchemasController::class, 'edit'], ['schemaId' => $schema->id]))
        ->assertOk()
        ->assertViewIs('graphql.schemas._edit')
        ->assertViewHas('schema', fn ($viewSchema) => $viewSchema->id === $schema->id)
        ->assertViewHas('title', $schema->name);

    get(action([SchemasController::class, 'editPublic']))
        ->assertOk()
        ->assertViewIs('graphql.schemas._edit')
        ->assertViewHas('schema')
        ->assertViewHas('token')
        ->assertViewHas('title', t('Edit the public GraphQL schema'));
});

it('updates an existing schema via the save action', function () {
    $schema = createSchemaForSchemasControllerTest();
    $updatedScope = schemaControllerScope();

    postJson(action([SchemasController::class, 'save']), [
        'schemaId' => $schema->id,
        'name' => 'Renamed Schema',
        'permissions' => $updatedScope,
    ])->assertOk();

    $updatedSchema = Gql::getSchemaById($schema->id);

    expect($updatedSchema?->name)->toBe('Renamed Schema')
        ->and($updatedSchema?->scope)->toBe($updatedScope);
});

it('returns not found when saving an unknown schema id', function () {
    postJson(action([SchemasController::class, 'save']), [
        'schemaId' => 999999,
        'name' => 'Missing Schema',
        'permissions' => schemaControllerScope(),
    ])->assertNotFound();
});

it('updates the public schema token settings and scope', function () {
    $expiryDate = '2026-12-31 15:30';
    $expectedExpiryDate = DateTimeHelper::toDateTime($expiryDate);

    postJson(action([SchemasController::class, 'savePublic']), [
        'permissions' => schemaControllerScope(),
        'enabled' => true,
        'expiryDate' => $expiryDate,
    ])->assertOk();

    $publicSchema = Gql::getPublicSchema();
    $publicToken = Gql::getPublicToken();

    expect($publicSchema?->scope)->toBe(schemaControllerScope())
        ->and($publicToken?->enabled)->toBeTrue()
        ->and($publicToken?->expiryDate?->getTimestamp())->toBe($expectedExpiryDate?->getTimestamp());
});

it('requires password confirmation for save-schema and save-public-schema', function () {
    Session::forget('auth.password_confirmed_at');

    postJson(cp_url('actions/graphql/save-schema'), [
        'name' => 'Protected Schema',
        'permissions' => schemaControllerScope(),
    ])->assertStatus(423);

    postJson(cp_url('actions/graphql/save-public-schema'), [
        'permissions' => schemaControllerScope(),
        'enabled' => true,
    ])->assertStatus(423);
});

it('validates the delete payload before deleting a schema', function () {
    postJson(action([SchemasController::class, 'destroy']), [])->assertUnprocessable()
        ->assertJsonValidationErrors(['id']);
});

function schemaControllerScope(): array
{
    return GqlHelper::createFullAccessSchema()->scope;
}

function createSchemaForSchemasControllerTest(array $overrides = []): GqlSchema
{
    $schema = new GqlSchema(array_merge([
        'name' => 'Schema '.bin2hex(random_bytes(4)),
        'scope' => schemaControllerScope(),
    ], $overrides));

    expect(Gql::saveSchema($schema))->toBeTrue();

    return $schema;
}
