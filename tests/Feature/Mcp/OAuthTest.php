<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Mcp\Events\CollectingAdminInstructions;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Public\Access;
use CraftCms\Cms\Mcp\PublicRouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    openssl_pkey_export($key, $privateKey);
    config()->set('passport.private_key', $privateKey);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
});

/** @return array<string, mixed> */
function mcpRegistration(array $overrides = []): array
{
    return array_replace([
        'client_name' => 'Test MCP client',
        'redirect_uris' => ['https://client.example/callback'],
    ], $overrides);
}

/** @return array<string, string> */
function mcpAuthorizationParameters(string $clientId, array $overrides = []): array
{
    return array_replace([
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => 'https://client.example/callback',
        'scope' => 'mcp:read mcp:write mcp:admin',
        'state' => 'client-state',
        'code_challenge_method' => 'S256',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 43), true)), '+/', '-_'), '='),
    ], $overrides);
}

function mcpExchangeAuthorizationCode(TestCase $test, string $clientId, string $location): void
{
    parse_str(parse_url($location, PHP_URL_QUERY), $callback);

    $test->postJson(route('passport.token'), [
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'redirect_uri' => 'https://client.example/callback',
        'code' => $callback['code'],
        'code_verifier' => str_repeat('v', 43),
    ])->assertOk();
}

it('discovers and completes dynamic client registration and authorization', function (array $acceptHeaders): void {
    config()->set('passport.guard', 'host-guard');
    Passport::authorizationView(static fn (): Response => new Response('Host authorization'));

    $endpoint = route('craft.cp.mcp.server');
    $challenge = $this->call(
        'POST',
        $endpoint,
        server: ['CONTENT_TYPE' => 'application/json', ...$acceptHeaders],
        content: Json::encode(McpRequest::payload('server/discover')),
    )
        ->assertUnauthorized()
        ->headers->get('WWW-Authenticate');

    expect($challenge)->toContain('scope="mcp:read mcp:write mcp:admin"');
    preg_match('/resource_metadata="([^"]+)"/', $challenge, $match);

    $resource = $this->getJson($match[1])
        ->assertOk()
        ->assertJsonPath('resource', $endpoint)
        ->assertJsonPath('scopes_supported', ['mcp:read', 'mcp:write', 'mcp:admin'])
        ->json();
    $issuer = $resource['authorization_servers'][0];
    $discovery = parse_url($issuer, PHP_URL_SCHEME).'://'.parse_url($issuer, PHP_URL_HOST).'/.well-known/oauth-authorization-server'.parse_url($issuer, PHP_URL_PATH);
    $metadata = $this->getJson($discovery)
        ->assertOk()
        ->assertJsonPath('token_endpoint', route('passport.token'))
        ->assertJsonPath('scopes_supported', ['mcp:read', 'mcp:write', 'mcp:admin'])
        ->json();

    expect($metadata)->toHaveKeys(['authorization_endpoint', 'registration_endpoint']);
    $registration = $this->postJson($metadata['registration_endpoint'], mcpRegistration())
        ->assertCreated()
        ->assertJsonPath('scope', 'mcp:read mcp:write mcp:admin')
        ->assertJsonPath('token_endpoint_auth_method', 'none')
        ->json();

    expect($metadata['token_endpoint_auth_methods_supported'] ?? [])
        ->toContain($registration['token_endpoint_auth_method']);

    $parameters = mcpAuthorizationParameters($registration['client_id']);

    $this->actingAs(User::query()->firstOrFail(), Cms::config()->getAuthGuard());
    $this->get($metadata['authorization_endpoint'].'?'.http_build_query($parameters))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('clientName', 'Test MCP client')
            ->where('redirectUri', 'https://client.example/callback')
            ->where('accessOptions', fn ($options): bool => collect($options)->pluck('value')->all() === ['mcp:read', 'mcp:write', 'mcp:admin'])
            ->where('scopes', [])
            ->where('approveAction', route('craft.cp.mcp.oauth.approve'))
            ->where('denyAction', route('craft.cp.mcp.oauth.deny')));

    $response = $this->post(route('craft.cp.mcp.oauth.approve'), [
        'auth_token' => session('authToken'),
    ])->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $callback);
    expect($callback['state'])->toBe($parameters['state']);

    $tokens = $this->postJson($metadata['token_endpoint'], [
        'grant_type' => 'authorization_code',
        'client_id' => $registration['client_id'],
        'redirect_uri' => $parameters['redirect_uri'],
        'code' => $callback['code'],
        'code_verifier' => str_repeat('v', 43),
    ])->assertOk()->json();

    Auth::forgetGuards();
    Route::getRoutes()->getByName('craft.cp.mcp.server')->flushController();
    $this->postJson($endpoint, McpRequest::payload('server/discover'), [
        ...McpRequest::headers('server/discover'),
        'Authorization' => 'Bearer '.$tokens['access_token'],
    ])->assertOk()->assertJsonPath('result.supportedVersions.0', '2026-07-28');

    $hostResponse = app(AuthorizationViewResponse::class)->withParameters([])->toResponse(request());

    expect($hostResponse->getContent())->toBe('Host authorization');
})->with([
    'JSON preferred' => [['HTTP_ACCEPT' => 'application/json']],
    'event stream preferred' => [['HTTP_ACCEPT' => 'text/event-stream, application/json']],
    'unspecified acceptance' => [['HTTP_ACCEPT' => null]],
]);

it('keeps the Passport consent page out of frames', function (): void {
    $clientId = $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration())->json('client_id');

    $this->actingAs(User::query()->firstOrFail(), config('passport.guard'));
    $this->get(route('passport.authorizations.authorize', mcpAuthorizationParameters($clientId)))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");
});

it('uses the configured control panel URL in OAuth metadata', function (): void {
    Cms::config()->baseCpUrl('https://cp.example.test');
    $resourcePath = '/.well-known/oauth-protected-resource/'.trim((string) Cms::config()->cpTrigger, '/').'/mcp';
    $expectedResource = 'https://cp.example.test/'.trim((string) Cms::config()->cpTrigger, '/').'/mcp';

    $this->getJson('https://attacker.example'.$resourcePath)
        ->assertOk()
        ->assertJsonPath('resource', $expectedResource)
        ->assertJsonPath('authorization_servers.0', $expectedResource);

    $this->getJson('https://attacker.example'.str_replace('oauth-protected-resource', 'oauth-authorization-server', $resourcePath))
        ->assertOk()
        ->assertJsonPath('issuer', $expectedResource)
        ->assertJsonPath('authorization_endpoint', $expectedResource.'/oauth/authorize')
        ->assertJsonPath('token_endpoint', 'https://cp.example.test/oauth/token')
        ->assertJsonPath('registration_endpoint', $expectedResource.'/oauth/register');
});

it('restricts dynamic client registration to configured redirect domains', function (): void {
    config()->set('mcp.redirect_domains', ['https://client.example']);

    $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration())->assertCreated();
    $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration([
        'redirect_uris' => ['https://attacker.example/callback'],
    ]))->assertStatus(400)->assertJsonPath('error', 'invalid_redirect_uri');
});

it('accepts configured native client redirects', function (string $redirect, array $domains, array $schemes): void {
    config()->set('mcp.redirect_domains', $domains);
    config()->set('mcp.custom_schemes', $schemes);

    $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration([
        'redirect_uris' => [$redirect],
    ]))->assertCreated()->assertJsonPath('redirect_uris.0', $redirect);
})->with([
    'approved scheme' => ['vscode://callback/complete', [], ['vscode']],
    'loopback hostname with dynamic port' => ['http://localhost:49152/callback', ['http://localhost'], []],
    'IPv4 loopback with dynamic port' => ['http://127.0.0.1:49152/callback', ['http://127.0.0.1'], []],
    'IPv6 loopback with dynamic port' => ['http://[::1]:49152/callback', ['http://[::1]'], []],
]);

it('rejects unconfigured native client redirects', function (string $redirect, array $domains, array $schemes): void {
    config()->set('mcp.redirect_domains', $domains);
    config()->set('mcp.custom_schemes', $schemes);

    $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration([
        'redirect_uris' => [$redirect],
    ]))->assertStatus(400)->assertJsonPath('error', 'invalid_redirect_uri');
})->with([
    'unapproved scheme' => ['vscode://callback/complete', ['*'], ['cursor']],
    'unapproved loopback' => ['http://127.0.0.1:49152/callback', ['https://client.example'], []],
]);

it('returns OAuth errors for malformed registration metadata', function (array $metadata, string $error): void {
    $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration($metadata))
        ->assertStatus(400)
        ->assertJsonPath('error', $error)
        ->assertJsonStructure(['error_description']);
})->with([
    'missing redirects' => [['redirect_uris' => []], 'invalid_redirect_uri'],
    'malformed redirect' => [['redirect_uris' => ['not-a-url']], 'invalid_redirect_uri'],
    'malformed name' => [['client_name' => ['unexpected']], 'invalid_client_metadata'],
]);

it('answers CORS preflight requests without authentication or collecting user instructions', function (bool $public): void {
    config()->set('app.url', 'https://craft.test');
    Event::listen(CollectingAdminInstructions::class, function (): void {
        app(McpActor::class)->user();
    });

    if ($public) {
        app()->instance(Settings::class, new Settings(['publicEnabled' => true, 'publicRoute' => '/_test/public-preflight']));
        app()->forgetInstance(Access::class);
        app(PublicRouteRegistrar::class)->register();
    }

    $response = $this->call('OPTIONS', $public ? '/_test/public-preflight' : route('craft.cp.mcp.server'), server: [
        'HTTP_ORIGIN' => 'https://craft.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'DELETE',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Mcp-Session-Id, Mcp-Param-Cursor',
    ])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://craft.test')
        ->assertHeader('Access-Control-Expose-Headers', 'Mcp-Session-Id');

    expect(explode(', ', $response->headers->get('Access-Control-Allow-Methods')))->toContain('DELETE')
        ->and(explode(', ', $response->headers->get('Access-Control-Allow-Headers')))->toContain('Mcp-Session-Id', 'Mcp-Param-Cursor');
})->with(['admin server' => false, 'public server' => true]);

it('requires both the MCP scope and Craft endpoint permissions', function (array $scopes, array $permissions, string $method): void {
    Edition::set(Edition::Pro);
    $user = User::query()->firstOrFail();
    $user->admin = false;
    $user->save();
    $userPermissions = app(UserPermissions::class);
    $userPermissions->saveUserPermissions($user->id, ['accessCp', 'useCraftMcp']);
    Passport::actingAs($user, ['mcp:read'], 'craft-mcp');
    $endpoint = route('craft.cp.mcp.server');
    $headers = McpRequest::headers('server/discover');

    $this->postJson($endpoint, McpRequest::payload('server/discover'), $headers)
        ->assertOk()->assertJsonPath('result.supportedVersions.0', '2026-07-28');

    $userPermissions->saveUserPermissions($user->id, $permissions);
    Passport::actingAs($user, $scopes, 'craft-mcp');
    Route::getRoutes()->getByName('craft.cp.mcp.server')->flushController();

    $this->json($method, $endpoint, McpRequest::payload('server/discover'), $headers)->assertForbidden();
})->with([
    'missing MCP scope' => [[], ['accessCp', 'useCraftMcp']],
    'missing control panel permission' => [['mcp:read'], ['useCraftMcp']],
    'missing MCP permission' => [['mcp:read'], ['accessCp']],
])->with(['POST', 'DELETE']);

it('grants the access level chosen on the consent screen', function (bool $admin, array $approval, array $options, array $granted): void {
    Edition::set(Edition::Pro);
    $user = User::query()->firstOrFail();
    $user->admin = $admin;
    $user->save();
    app(UserPermissions::class)->saveUserPermissions($user->id, ['accessCp', 'useCraftMcp']);

    $clientId = $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration())->json('client_id');
    $parameters = mcpAuthorizationParameters($clientId);

    $this->actingAs($user, Cms::config()->getAuthGuard());
    $this->get(route('craft.cp.mcp.oauth.authorize').'?'.http_build_query($parameters))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('accessOptions', fn ($accessOptions): bool => collect($accessOptions)->pluck('value')->all() === $options));

    $location = $this->post(route('craft.cp.mcp.oauth.approve'), [
        'auth_token' => session('authToken'),
        ...$approval,
    ])->assertRedirect()->headers->get('Location');
    mcpExchangeAuthorizationCode($this, $clientId, $location);

    expect(Passport::token()->newQuery()->sole()->scopes)->toBe($granted);
})->with([
    'read by default' => [true, [], ['mcp:read', 'mcp:write', 'mcp:admin'], ['mcp:read']],
    'write' => [true, ['access' => 'mcp:write'], ['mcp:read', 'mcp:write', 'mcp:admin'], ['mcp:read', 'mcp:write']],
    'admin' => [true, ['access' => 'mcp:admin'], ['mcp:read', 'mcp:write', 'mcp:admin'], ['mcp:read', 'mcp:write', 'mcp:admin']],
    'admin for a non-admin' => [false, ['access' => 'mcp:admin'], ['mcp:read', 'mcp:write'], ['mcp:read']],
]);

it('reuses the earlier access level when a client authorizes again', function (): void {
    $clientId = $this->postJson(route('craft.cp.mcp.oauth.register'), mcpRegistration())->json('client_id');
    $authorizeUrl = fn (string $scope): string => route('craft.cp.mcp.oauth.authorize').'?'.http_build_query(mcpAuthorizationParameters($clientId, ['scope' => $scope]));

    $this->actingAs(User::query()->firstOrFail(), Cms::config()->getAuthGuard());
    $this->get($authorizeUrl('mcp:read mcp:write mcp:admin'))->assertOk();
    $location = $this->post(route('craft.cp.mcp.oauth.approve'), [
        'auth_token' => session('authToken'),
        'access' => 'mcp:write',
    ])->headers->get('Location');
    mcpExchangeAuthorizationCode($this, $clientId, $location);

    $location = $this->get($authorizeUrl('mcp:read mcp:write mcp:admin'))->assertRedirect()->headers->get('Location');
    mcpExchangeAuthorizationCode($this, $clientId, $location);

    expect(Passport::token()->newQuery()->pluck('scopes')->all())->toBe([['mcp:read', 'mcp:write'], ['mcp:read', 'mcp:write']]);

    $this->get($authorizeUrl('mcp:admin'))->assertOk();
});

it('exposes only the tools the token scope covers', function (array $scopes, array $visible, array $hidden): void {
    Passport::actingAs(User::query()->firstOrFail(), $scopes, 'craft-mcp');

    $names = array_column(McpRequest::tools($this), 'name');

    expect(array_diff($visible, $names))->toBe([])
        ->and(array_intersect($hidden, $names))->toBe([]);
})->with([
    'read' => [['mcp:read'], ['elements.list', 'assets.transform-url'], ['elements.create', 'configuration.list']],
    'write' => [['mcp:write'], ['elements.list', 'elements.create'], ['configuration.list']],
    'admin' => [['mcp:admin'], ['elements.list', 'elements.create', 'configuration.list'], []],
]);

it('rejects calls to tools outside the token scope', function (): void {
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:read'], 'craft-mcp');

    McpRequest::send($this, 'tools/call', ['name' => 'elements.delete', 'arguments' => ['type' => 'entry', 'id' => 1]])
        ->assertJsonPath('error.message', 'Tool not found: "elements.delete".');
});

it('requires write access for upload sessions', function (array $scopes, int $status): void {
    $user = User::query()->firstOrFail();
    Passport::actingAs($user, ['mcp:write'], 'craft-mcp');
    $upload = McpRequest::send($this, 'tools/call', [
        'name' => 'assets.upload.prepare',
        'arguments' => ['filename' => 'notes.txt', 'size' => 3, 'volumeId' => Volume::factory()->create()->id],
    ])->assertJsonPath('result.isError', false)->json('result.structuredContent.upload');

    Passport::actingAs($user, $scopes, 'craft-mcp');

    $this->getJson($upload['urls']['status'])->assertStatus($status);
})->with([
    'read' => [['mcp:read'], 403],
    'write' => [['mcp:write'], 200],
]);
