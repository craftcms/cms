<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Http\Controllers\Gql\TokensController;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
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

    postJson(cp_url('actions/graphql/save-token'), [
        'name' => 'Protected token',
        'accessToken' => 'protected-token',
        'enabled' => true,
        'schema' => createSchemaForTokensControllerTest()->id,
    ])->assertForbidden();

    postJson(cp_url('actions/graphql/fetch-token'), [
        'tokenUid' => $token->uid,
    ])->assertForbidden();

    postJson(cp_url('actions/graphql/generate-token'))->assertForbidden();

    postJson(cp_url('actions/graphql/delete-token'), [
        'id' => $token->id,
    ])->assertForbidden();
});

it('allows token pages and actions without admin changes', function () {
    Cms::config()->allowAdminChanges = false;
    $schema = createSchemaForTokensControllerTest();

    get(action([TokensController::class, 'index']))->assertOk();
    get(action([TokensController::class, 'create']))->assertOk();

    postJson(cp_url('actions/graphql/save-token'), [
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
        ->assertOk()
        ->assertViewIs('graphql.tokens._index');

    get(action([TokensController::class, 'create']))
        ->assertOk()
        ->assertViewIs('graphql.tokens._edit')
        ->assertViewHas('token')
        ->assertViewHas('accessToken', fn ($accessToken) => is_string($accessToken) && $accessToken !== '')
        ->assertViewHas('schemaOptions', fn (array $options) => collect($options)
            ->pluck('value')
            ->doesntContain($publicSchema?->id))
        ->assertViewHas('title', t('Create a new GraphQL token'));

    get(action([TokensController::class, 'edit'], ['tokenId' => $token->id]))
        ->assertOk()
        ->assertViewIs('graphql.tokens._edit')
        ->assertViewHas('token', fn ($viewToken) => $viewToken->id === $token->id)
        ->assertViewHas('title', $token->name);
});

it('shows a blank schema option when editing a token without a schema', function () {
    createSchemaForTokensControllerTest();
    $token = createTokenForTokensControllerTest(overrides: ['schemaId' => null]);

    get(action([TokensController::class, 'edit'], ['tokenId' => $token->id]))
        ->assertOk()
        ->assertViewHas('schemaOptions', fn (array $options) => $options[0]['value'] === '');
});

it('returns not found for invalid, public, or missing token ids', function () {
    get(action([TokensController::class, 'edit'], ['tokenId' => 999999]))->assertNotFound();

    $publicToken = Gql::getPublicToken();
    expect($publicToken)->not->toBeNull();

    $publicToken->enabled = true;
    expect(Gql::saveToken($publicToken))->toBeTrue();
    expect($publicToken->id)->not->toBeNull();

    get(action([TokensController::class, 'edit'], ['tokenId' => $publicToken->id]))->assertNotFound();

    postJson(cp_url('actions/graphql/save-token'), [
        'tokenId' => 999999,
        'name' => 'Missing Token',
        'accessToken' => 'missing-token',
        'enabled' => true,
    ])->assertNotFound();
});

it('requires password confirmation for save-token and fetch-token', function () {
    $schema = createSchemaForTokensControllerTest();
    $token = createTokenForTokensControllerTest($schema);

    Session::forget('auth.password_confirmed_at');

    postJson(cp_url('actions/graphql/save-token'), [
        'name' => 'Protected token',
        'accessToken' => 'protected-token',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertStatus(423);

    postJson(cp_url('actions/graphql/fetch-token'), [
        'tokenUid' => $token->uid,
    ])->assertStatus(423);
});

it('saves, updates, fetches, generates, and deletes tokens', function () {
    $schema = createSchemaForTokensControllerTest();
    $updatedSchema = createSchemaForTokensControllerTest();

    postJson(cp_url('actions/graphql/save-token'), [
        'name' => 'API Token',
        'accessToken' => 'api-token-1',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertOk();

    $token = Gql::getTokenByName('API Token');

    expect($token)->not->toBeNull();

    postJson(cp_url('actions/graphql/save-token'), [
        'tokenId' => $token->id,
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

    postJson(cp_url('actions/graphql/fetch-token'), [
        'tokenUid' => $token->uid,
    ])->assertOk()
        ->assertJsonPath('accessToken', 'api-token-2');

    postJson(cp_url('actions/graphql/generate-token'))
        ->assertOk()
        ->assertJsonStructure(['accessToken']);

    postJson(cp_url('actions/graphql/delete-token'), [
        'id' => $token->id,
    ])->assertOk();

    expect(Gql::getTokenById($token->id))->toBeNull();
});

it('re-renders the token edit screen for html failures and returns model errors for json', function () {
    $schema = createSchemaForTokensControllerTest();

    post(cp_url('actions/graphql/save-token'), [
        'accessToken' => 'missing-name-token',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertOk()
        ->assertSee(t('Create a new GraphQL token'));

    postJson(cp_url('actions/graphql/save-token'), [
        'accessToken' => 'missing-name-token-json',
        'enabled' => true,
        'schema' => $schema->id,
    ])->assertStatus(400)
        ->assertJsonStructure([
            'modelName',
            'modelClass',
            'token',
            'errors' => ['name'],
        ]);
});

it('requires json requests for fetch and generate and validates delete payloads', function () {
    $token = createTokenForTokensControllerTest();

    post(cp_url('actions/graphql/fetch-token'), [
        'tokenUid' => $token->uid,
    ])->assertBadRequest();

    post(cp_url('actions/graphql/generate-token'))->assertBadRequest();

    postJson(cp_url('actions/graphql/delete-token'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id']);
});

it('returns bad request for invalid token uids', function () {
    postJson(cp_url('actions/graphql/fetch-token'), [
        'tokenUid' => 'missing-token-uid',
    ])->assertBadRequest();
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
