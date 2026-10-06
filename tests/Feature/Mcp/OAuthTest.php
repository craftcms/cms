<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Facades\Auth;
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
        'scope' => 'mcp:use',
        'state' => 'client-state',
        'code_challenge_method' => 'S256',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 43), true)), '+/', '-_'), '='),
    ], $overrides);
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

    expect($challenge)->toContain('scope="mcp:use"');
    preg_match('/resource_metadata="([^"]+)"/', $challenge, $match);

    $resource = $this->getJson($match[1])
        ->assertOk()
        ->assertJsonPath('resource', $endpoint)
        ->assertJsonPath('scopes_supported.0', 'mcp:use')
        ->json();
    $issuer = $resource['authorization_servers'][0];
    $discovery = parse_url($issuer, PHP_URL_SCHEME).'://'.parse_url($issuer, PHP_URL_HOST).'/.well-known/oauth-authorization-server'.parse_url($issuer, PHP_URL_PATH);
    $metadata = $this->getJson($discovery)
        ->assertOk()
        ->assertJsonPath('token_endpoint', route('passport.token'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use')
        ->json();

    expect($metadata)->toHaveKeys(['authorization_endpoint', 'registration_endpoint']);
    $registration = $this->postJson($metadata['registration_endpoint'], mcpRegistration())
        ->assertCreated()
        ->assertJsonPath('scope', 'mcp:use')
        ->assertJsonPath('token_endpoint_auth_method', 'none')
        ->json();
    $parameters = mcpAuthorizationParameters($registration['client_id']);

    $this->actingAs(User::query()->firstOrFail(), Cms::config()->getAuthGuard());
    $this->get($metadata['authorization_endpoint'].'?'.http_build_query($parameters))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('clientName', 'Test MCP client')
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

it('requires both the MCP scope and Craft endpoint permissions', function (array $scopes, array $permissions): void {
    Edition::set(Edition::Pro);
    $user = User::query()->firstOrFail();
    $user->admin = false;
    $user->save();
    $userPermissions = app(UserPermissions::class);
    $userPermissions->saveUserPermissions($user->id, ['accessCp', 'useCraftMcp']);
    Passport::actingAs($user, ['mcp:use'], 'craft-mcp');
    $endpoint = route('craft.cp.mcp.server');
    $headers = McpRequest::headers('server/discover');

    $this->postJson($endpoint, McpRequest::payload('server/discover'), $headers)
        ->assertOk()->assertJsonPath('result.supportedVersions.0', '2026-07-28');

    $userPermissions->saveUserPermissions($user->id, $permissions);
    Passport::actingAs($user, $scopes, 'craft-mcp');
    Route::getRoutes()->getByName('craft.cp.mcp.server')->flushController();

    $this->postJson($endpoint, McpRequest::payload('server/discover'), $headers)->assertForbidden();
})->with([
    'missing MCP scope' => [[], ['accessCp', 'useCraftMcp']],
    'missing control panel permission' => [['mcp:use'], ['useCraftMcp']],
    'missing MCP permission' => [['mcp:use'], ['accessCp']],
]);
