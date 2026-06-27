<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Http\Controllers\Gql\SchemasController;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\patchJson;
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

    postJson(action([SchemasController::class, 'store']), [
        'name' => 'Protected Schema',
        'permissions' => schemaControllerScope(),
    ])->assertForbidden();

    patchJson(action([SchemasController::class, 'update'], ['schemaId' => 'public']), [
        'permissions' => schemaControllerScope(),
        'enabled' => true,
    ])->assertForbidden();

    deleteJson(action([SchemasController::class, 'destroy'], ['schemaId' => $schema->id]))->assertForbidden();
});

it('forbids schema pages when admin changes are disabled', function () {
    $schema = createSchemaForSchemasControllerTest();
    Cms::config()->allowAdminChanges = false;

    get(action([SchemasController::class, 'index']))->assertForbidden();
    get(action([SchemasController::class, 'create']))->assertForbidden();
    get(action([SchemasController::class, 'edit'], ['schemaId' => $schema->id]))->assertForbidden();
    get(action([SchemasController::class, 'edit'], ['schemaId' => 'public']))->assertForbidden();
});

it('renders the schema index, create, edit, and public edit screens', function () {
    $schema = createSchemaForSchemasControllerTest([
        'scope' => ['directive:parseRefs'],
    ]);

    get(action([SchemasController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('graphql/schemas/Index'));

    get(action([SchemasController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/schemas/Edit')
            ->where('schema.id', null)
            ->where('title', 'Create a new GraphQL Schema')
            ->where('permissions', fn ($permissions) => collect($permissions)
                ->pluck('permissions')
                ->contains(fn (array $groupPermissions) => array_key_exists('directive:parseRefs', $groupPermissions))));

    get(action([SchemasController::class, 'edit'], ['schemaId' => $schema->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/schemas/Edit')
            ->where('schema.id', $schema->id)
            ->where('schema.scope', ['directive:parseRefs'])
            ->where('title', $schema->name));

    get(action([SchemasController::class, 'edit'], ['schemaId' => 'public']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/schemas/Edit')
            ->where('schema.isPublic', true)
            ->has('token')
            ->where('title', 'Edit the public GraphQL schema'));
});

it('creates and updates schemas', function () {
    $scope = schemaControllerScope();

    postJson(action([SchemasController::class, 'store']), [
        'name' => 'Created Schema',
        'permissions' => $scope,
    ])->assertOk();

    $schema = collect(Gql::getSchemas())->first(fn (GqlSchema $schema) => $schema->name === 'Created Schema');

    expect($schema)->not->toBeNull()
        ->and($schema?->scope)->toBe($scope);

    $updatedScope = ['directive:parseRefs'];

    patchJson(action([SchemasController::class, 'update'], ['schemaId' => $schema->id]), [
        'name' => 'Renamed Schema',
        'permissions' => $updatedScope,
    ])->assertOk();

    $updatedSchema = Gql::getSchemaById($schema->id);

    expect($updatedSchema?->name)->toBe('Renamed Schema')
        ->and($updatedSchema?->scope)->toBe($updatedScope);
});

it('returns not found when updating an unknown schema id', function () {
    patchJson(action([SchemasController::class, 'update'], ['schemaId' => 999999]), [
        'name' => 'Missing Schema',
        'permissions' => schemaControllerScope(),
    ])->assertNotFound();
});

it('updates the public schema token settings and scope', function () {
    $expiryDate = '2026-12-31 15:30';
    $expectedExpiryDate = DateTimeHelper::toDateTime($expiryDate);

    patchJson(action([SchemasController::class, 'update'], ['schemaId' => 'public']), [
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

it('requires password confirmation for schema mutations', function () {
    $schema = createSchemaForSchemasControllerTest();

    Session::forget('auth.password_confirmed_at');

    postJson(action([SchemasController::class, 'store']), [
        'name' => 'Protected Schema',
        'permissions' => schemaControllerScope(),
    ])->assertStatus(423);

    patchJson(action([SchemasController::class, 'update'], ['schemaId' => $schema->id]), [
        'name' => 'Protected Schema',
        'permissions' => schemaControllerScope(),
    ])->assertStatus(423);

    patchJson(action([SchemasController::class, 'update'], ['schemaId' => 'public']), [
        'permissions' => schemaControllerScope(),
        'enabled' => true,
    ])->assertStatus(423);
});

it('requires a schemaId before deletion', function () {
    expect(fn () => deleteJson(action([SchemasController::class, 'destroy']), []))
        ->toThrow(UrlGenerationException::class);
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
