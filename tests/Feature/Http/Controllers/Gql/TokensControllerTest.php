<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Http\Controllers\Gql\TokensController;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Url;
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
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    Session::passwordConfirmed();
});

it('requires authentication for token pages', function () {
    $token = createTokenForTokensControllerTest();

    Auth::logout();

    get(cp_url('graphql/tokens'))->assertRedirect();
    get(cp_url('graphql/tokens/new'))->assertRedirect();
    get(action([TokensController::class, 'edit'], ['tokenId' => $token->id]))->assertRedirect();
});

it('requires admin access for token pages and actions', function () {
    $token = createTokenForTokensControllerTest();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    actingAs(User::findOne());

    get(cp_url('graphql/tokens'))->assertForbidden();
    get(cp_url('graphql/tokens/new'))->assertForbidden();
    get(action([TokensController::class, 'edit'], ['tokenId' => $token->id]))->assertForbidden();

    postJson(action([TokensController::class, 'store']), [
        'name' => 'Protected token',
        'accessToken' => 'protected-token',
        'enabled' => true,
        'schema' => createSchemaForTokensControllerTest()->id,
    ])->assertForbidden();

    postJson(action([TokensController::class, 'accessToken'], ['tokenId' => $token->id]))->assertForbidden();
    postJson(action([TokensController::class, 'generate']))->assertForbidden();
    deleteJson(action([TokensController::class, 'destroy'], ['tokenId' => $token->id]))->assertForbidden();
});

it('allows token pages and actions without admin changes', function () {
    Cms::config()->allowAdminChanges = false;
    $schema = createSchemaForTokensControllerTest();

    get(action([TokensController::class, 'index']))->assertOk();
    get(action([TokensController::class, 'create']))->assertOk();

    postJson(action([TokensController::class, 'store']), [
        'name' => 'No Admin Changes Token',
        'accessToken' => 'no-admin-changes-token',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertOk();

    expect(Gql::getTokenByName('No Admin Changes Token'))->not->toBeNull();
});

it('renders the token index, create, and edit screens', function () {
    $schema = createSchemaForTokensControllerTest();
    $token = createTokenForTokensControllerTest($schema);
    $publicSchema = Gql::getPublicSchema();

    get(action([TokensController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('graphql/tokens/Index'));

    get(action([TokensController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/tokens/Edit')
            ->where('token.id', null)
            ->where('title', 'Create a new GraphQL token')
            ->where('accessToken', fn ($accessToken) => is_string($accessToken) && $accessToken !== '')
            ->where('schemaOptions', fn ($options) => collect($options)
                ->pluck('value')
                ->doesntContain($publicSchema?->id)));

    get(action([TokensController::class, 'edit'], ['tokenId' => $token->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/tokens/Edit')
            ->where('token.id', $token->id)
            ->where('title', $token->name)
            ->where('accessToken', null));
});

it('shows a blank schema option when editing a token without a schema', function () {
    createSchemaForTokensControllerTest();
    $token = createTokenForTokensControllerTest(overrides: ['schemaId' => null]);

    get(action([TokensController::class, 'edit'], ['tokenId' => $token->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('graphql/tokens/Edit')
            ->where('schemaOptions.0.value', ''));
});

it('returns not found for invalid, public, or missing token ids', function () {
    get(action([TokensController::class, 'edit'], ['tokenId' => 999999]))->assertNotFound();

    $publicToken = Gql::getPublicToken();
    expect($publicToken)->not->toBeNull();

    $publicToken->enabled = true;
    expect(Gql::saveToken($publicToken))->toBeTrue();
    expect($publicToken->id)->not->toBeNull();

    get(action([TokensController::class, 'edit'], ['tokenId' => $publicToken->id]))->assertNotFound();

    patchJson(action([TokensController::class, 'update'], ['tokenId' => 999999]), [
        'name' => 'Missing Token',
        'accessToken' => 'missing-token',
        'enabled' => true,
    ])->assertNotFound();
});

it('requires password confirmation for saving and revealing tokens', function () {
    $schema = createSchemaForTokensControllerTest();
    $token = createTokenForTokensControllerTest($schema);

    Session::forget('auth.password_confirmed_at');

    postJson(action([TokensController::class, 'store']), [
        'name' => 'Protected token',
        'accessToken' => 'protected-token',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertStatus(423);

    postJson(action([TokensController::class, 'accessToken'], ['tokenId' => $token->id]))->assertStatus(423);
});

it('saves, updates, reveals, generates, and deletes tokens', function () {
    $schema = createSchemaForTokensControllerTest();
    $updatedSchema = createSchemaForTokensControllerTest();

    postJson(action([TokensController::class, 'store']), [
        'name' => 'API Token',
        'accessToken' => 'api-token-1',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertOk();

    $token = Gql::getTokenByName('API Token');

    expect($token)->not->toBeNull();

    patchJson(action([TokensController::class, 'update'], ['tokenId' => $token->id]), [
        'name' => 'Updated API Token',
        'accessToken' => 'api-token-2',
        'enabled' => false,
        'schema' => $updatedSchema->id,
        'expiryDate' => '2026-12-31 15:30',
    ])->assertOk();

    $token = Gql::getTokenById($token->id);

    expect($token?->name)->toBe('Updated API Token')
        ->and($token?->accessToken)->toBe('api-token-2')
        ->and($token?->enabled)->toBeFalse()
        ->and($token?->schemaId)->toBe($updatedSchema->id)
        ->and($token?->expiryDate?->getTimestamp())->toBe(DateTimeHelper::toDateTime('2026-12-31 15:30')?->getTimestamp());

    patchJson(action([TokensController::class, 'update'], ['tokenId' => $token->id]), [
        'name' => 'Updated API Token',
        'enabled' => false,
        'schema' => $updatedSchema->id,
        'expiryDate' => '',
    ])->assertOk();

    $token = Gql::getTokenById($token->id);

    expect($token?->expiryDate)->toBeNull()
        ->and($token?->accessToken)->toBe('api-token-2');

    postJson(action([TokensController::class, 'accessToken'], ['tokenId' => $token->id]))
        ->assertOk()
        ->assertJsonPath('accessToken', 'api-token-2');

    postJson(action([TokensController::class, 'generate']))
        ->assertOk()
        ->assertJsonStructure(['accessToken']);

    deleteJson(action([TokensController::class, 'destroy'], ['tokenId' => $token->id]))->assertOk();

    expect(Gql::getTokenById($token->id))->toBeNull();
});

it('redirects to the saved token edit page when saving and continuing', function () {
    $schema = createSchemaForTokensControllerTest();

    $response = post(action([TokensController::class, 'store']), [
        'name' => 'Continued Token',
        'accessToken' => 'continued-token',
        'enabled' => true,
        'schema' => $schema->id,
    ]);

    $token = Gql::getTokenByName('Continued Token');

    expect($token)->not->toBeNull();

    $response->assertRedirect(Url::cpUrl("graphql/tokens/$token->id"));
});

it('returns inertia validation errors for token save failures', function () {
    $schema = createSchemaForTokensControllerTest();

    post(action([TokensController::class, 'store']), [
        'accessToken' => 'missing-name-token-json',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertSessionHasErrors('name');
});

it('requires a tokenId before deletion', function () {
    expect(fn () => deleteJson(action([TokensController::class, 'destroy']), []))
        ->toThrow(UrlGenerationException::class);
});

function tokenControllerScope(): array
{
    return GqlHelper::createFullAccessSchema()->scope;
}

function createSchemaForTokensControllerTest(array $overrides = []): GqlSchema
{
    $schema = new GqlSchema(array_merge([
        'name' => 'Schema '.bin2hex(random_bytes(4)),
        'scope' => tokenControllerScope(),
    ], $overrides));

    expect(Gql::saveSchema($schema))->toBeTrue();

    return $schema;
}

function createTokenForTokensControllerTest(?GqlSchema $schema = null, array $overrides = []): GqlToken
{
    $schema ??= createSchemaForTokensControllerTest();

    $token = new GqlToken(array_merge([
        'name' => 'Token '.bin2hex(random_bytes(4)),
        'schemaId' => $schema->id,
        'accessToken' => 'token-'.bin2hex(random_bytes(8)),
        'enabled' => true,
    ], $overrides));

    expect(Gql::saveToken($token))->toBeTrue();

    return $token;
}
