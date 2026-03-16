<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\Facades\Gql as GqlFacade;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\action_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    gqlDisablePublicToken();
    gqlActivateFullAccessSchema('Active '.bin2hex(random_bytes(4)));
});

it('returns the missing-query error payload', function () {
    get(action_url('graphql/api'))
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('errors.0.message', 'No GraphQL query was supplied');
});

it('returns graphql syntax errors for malformed queries', function () {
    get(action_url('graphql/api').'?query=bogus}')
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertSee('Syntax Error');
});

it('supports application graphql requests', function () {
    $this->call('POST', action_url('graphql/api'), [], [], [], [
        'CONTENT_TYPE' => 'application/graphql',
        'HTTP_ACCEPT' => 'application/graphql-response+json',
    ], '{__typename}')
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('data.__typename', 'Query');
});

it('lets get params override the request body', function () {
    $this->call('POST', action_url('graphql/api').'?query=%7B__typename%7D', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/graphql-response+json',
    ], json_encode([
        'query' => 'bogus}',
    ]))
        ->assertOk()
        ->assertJsonPath('data.__typename', 'Query');
});

it('supports batch execution', function () {
    $this->call('POST', action_url('graphql/api'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/graphql-response+json',
    ], json_encode([
        ['query' => '{__typename}'],
        ['query' => '{__typename}'],
    ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('0.data.__typename', 'Query')
        ->assertJsonPath('1.data.__typename', 'Query');
});

it('enforces the graphql batch size limit', function () {
    app(GeneralConfig::class)->maxGraphqlBatchSize = 1;

    $this->call('POST', action_url('graphql/api'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/graphql-response+json',
    ], json_encode([
        ['query' => '{__typename}'],
        ['query' => '{__typename}'],
    ]))
        ->assertBadRequest();
});

it('rejects invalid variables json in query params', function () {
    get(action_url('graphql/api').'?query=%7B__typename%7D&variables=%7B')
        ->assertBadRequest();
});

it('rejects invalid authorization headers for disabled tokens', function () {
    $schema = createApiSchema();
    $token = createApiToken($schema, ['enabled' => false]);

    get(action_url('graphql/api').'?query=%7B__typename%7D', [
        'Authorization' => 'Bearer '.$token->accessToken,
    ])->assertBadRequest();
});

it('falls back to the public token when available', function () {
    setActiveSchema(new GqlSchema([
        'name' => 'No Sites',
        'scope' => [],
    ]));

    $token = enablePublicToken();

    expect($token->getIsValid())->toBeTrue()
        ->and(GqlFacade::getPublicSchema()->scope)->not->toBeEmpty()
        ->and(GqlHelper::getAllowedSites(GqlFacade::getPublicSchema()))->not->toBeEmpty();

    get(action_url('graphql/api').'?query=%7B__typename%7D')
        ->assertOk()
        ->assertJsonPath('data.__typename', 'Query');
});

it('lets admins use the x-craft-gql-schema header', function () {
    actingAs(User::findOne());

    get(action_url('graphql/api').'?query=%7B__typename%7D', [
        'X-Craft-Gql-Schema' => '*',
    ])->assertOk()
        ->assertJsonPath('data.__typename', 'Query');
});

it('denies access when the schema has no site permissions', function () {
    setActiveSchema(new GqlSchema([
        'name' => 'No Sites',
        'scope' => [],
    ]));

    get(action_url('graphql/api').'?query=%7B__typename%7D')
        ->assertForbidden()
        ->assertSee('Schema doesn’t have access');
});

it('adds no-cache headers for mutations unless caching is forced', function () {
    $response = get(action_url('graphql/api').'?query=mutation%20%7Bbogus%7D')
        ->assertOk()
        ->assertHeader('Pragma', 'no-cache')
        ->assertHeader('Expires', '0');

    expect($response->headers->get('Cache-Control'))
        ->toContain('no-cache')
        ->toContain('no-store')
        ->toContain('must-revalidate');

    $response = get(action_url('graphql/api').'?query=mutation%20%7Bbogus%7D', [
        'X-Craft-Gql-Cache' => 'cache',
    ])->assertOk()
        ->assertHeaderMissing('Pragma')
        ->assertHeaderMissing('Expires');

    expect($response->headers->get('Cache-Control'))
        ->not->toBe('no-cache, no-store, must-revalidate');
});

function setActiveSchema(GqlSchema $schema): void
{
    GqlFacade::setActiveSchema($schema);
}

function enablePublicToken(): GqlToken
{
    $schema = GqlFacade::getPublicSchema();
    $schema->scope = GqlHelper::createFullAccessSchema()->scope;
    GqlFacade::saveSchema($schema);

    $token = GqlFacade::getPublicToken();
    $token->setSchema($schema);
    $token->enabled = true;
    GqlFacade::saveToken($token);

    new ReflectionProperty(Gql::class, '_publicToken')
        ->setValue(app(Gql::class), $token);

    return $token;
}

function createApiSchema(array $overrides = []): GqlSchema
{
    $schema = new GqlSchema(array_merge([
        'name' => 'Schema '.bin2hex(random_bytes(4)),
        'scope' => GqlHelper::createFullAccessSchema()->scope,
    ], $overrides));

    expect(GqlFacade::saveSchema($schema))->toBeTrue();

    return $schema;
}

function createApiToken(?GqlSchema $schema = null, array $overrides = []): GqlToken
{
    $schema ??= createApiSchema();

    $token = new GqlToken(array_merge([
        'name' => 'Token '.bin2hex(random_bytes(4)),
        'schemaId' => $schema->id,
        'accessToken' => 'token-'.bin2hex(random_bytes(8)),
        'enabled' => true,
    ], $overrides));

    expect(GqlFacade::saveToken($token))->toBeTrue();

    return $token;
}
