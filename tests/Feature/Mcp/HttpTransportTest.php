<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\Public\Access;
use CraftCms\Cms\Mcp\PublicRouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);

    $this->mcpCachePath = sys_get_temp_dir().'/craft-mcp-'.bin2hex(random_bytes(8));
    config()->set('cache.default', 'file');
    config()->set('cache.stores.file.path', $this->mcpCachePath);

    app()->instance(Settings::class, new Settings([
        'publicEnabled' => true,
        'publicRoute' => '/_test/dual-protocol-mcp',
        'publicTools' => ['craft-context-get'],
    ]));
    app()->forgetInstance(Access::class);
    app(PublicRouteRegistrar::class)->register();
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:read'], 'craft-mcp');
});

afterEach(function (): void {
    File::deleteDirectory($this->mcpCachePath);
});

function mcpHttpRequest(TestCase $test, string $endpoint, string $method, array $payload = [], array $headers = []): TestResponse
{
    Route::getRoutes()->getByName('craft.cp.mcp.server')->flushController();
    foreach (Route::getRoutes()->getRoutes() as $route) {
        if ($route->getName() === 'craft.mcp.public') {
            $route->flushController();
        }
    }

    Cache::forgetDriver('file');
    app()->forgetInstance('cache.store');

    return $test->json($method, $endpoint, $payload, $headers);
}

function mcpHttpInitialize(TestCase $test, string $endpoint, string $version = '2025-03-26'): string
{
    $response = mcpHttpRequest($test, $endpoint, 'POST', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => $version,
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'Handshake client', 'version' => '1.0'],
        ],
    ])->assertOk()->assertJsonPath('result.protocolVersion', $version);

    $session = $response->headers->get('Mcp-Session-Id');
    expect($session)->toBeString()->not->toBeEmpty();

    return $session;
}

it('serves both HTTP protocols and persists handshake sessions until teardown', function (bool $public, string $version): void {
    $endpoint = $public ? '/_test/dual-protocol-mcp' : route('craft.cp.mcp.server');
    $session = mcpHttpInitialize($this, $endpoint, $version);
    $headers = ['Mcp-Session-Id' => $session, 'Mcp-Protocol-Version' => $version];

    mcpHttpRequest($this, $endpoint, 'POST', [
        'jsonrpc' => '2.0', 'method' => 'notifications/initialized',
    ], $headers)->assertAccepted();

    $list = ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'];
    $tools = mcpHttpRequest($this, $endpoint, 'POST', $list, $headers)
        ->assertOk()->json('result.tools');
    $names = array_column($tools, 'name');

    if ($public) {
        expect($names)->toBe(['craft-context-get']);
    } else {
        expect($names)->toContain('elements.list')->not->toContain('elements.create', 'configuration.list');
    }

    mcpHttpRequest($this, $endpoint, 'POST', McpRequest::payload('tools/list'), McpRequest::headers('tools/list'))
        ->assertOk()->assertHeaderMissing('Mcp-Session-Id')->assertJsonPath('result.tools', $tools);

    mcpHttpRequest($this, $endpoint, 'DELETE', headers: $headers)->assertOk();
    mcpHttpRequest($this, $endpoint, 'POST', $list, $headers)->assertNotFound();
})->with(['admin' => false, 'public' => true])->with(['2025-03-26', '2025-06-18', '2025-11-25']);

it('isolates handshake sessions by endpoint, user, and scopes', function (string $boundary): void {
    $admin = route('craft.cp.mcp.server');
    $public = '/_test/dual-protocol-mcp';
    $owner = User::query()->firstOrFail();
    $source = $boundary === 'public to admin' ? $public : $admin;
    $target = $boundary === 'admin to public' ? $public : $admin;

    if ($boundary === 'scope downgrade') {
        Passport::actingAs($owner, ['mcp:admin'], 'craft-mcp');
    }

    $session = mcpHttpInitialize($this, $source);
    $headers = ['Mcp-Session-Id' => $session, 'Mcp-Protocol-Version' => '2025-03-26'];
    $list = ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'];

    if ($boundary === 'user') {
        Passport::actingAs(User::factory()->create(['admin' => true]), ['mcp:read'], 'craft-mcp');
    } elseif ($boundary === 'scopes') {
        Passport::actingAs($owner, ['mcp:admin'], 'craft-mcp');
    } elseif ($boundary === 'scope downgrade') {
        Passport::actingAs($owner, ['mcp:read'], 'craft-mcp');
    }

    mcpHttpRequest($this, $target, 'POST', $list, $headers)->assertNotFound();
    mcpHttpRequest($this, $target, 'DELETE', headers: $headers)->assertOk();

    Passport::actingAs($owner, [$boundary === 'scope downgrade' ? 'mcp:admin' : 'mcp:read'], 'craft-mcp');
    mcpHttpRequest($this, $source, 'POST', $list, $headers)->assertOk()->assertJsonStructure(['result' => ['tools']]);
})->with(['user', 'scopes', 'scope downgrade', 'admin to public', 'public to admin']);

it('rechecks public approvals within an existing handshake session', function (): void {
    $endpoint = '/_test/dual-protocol-mcp';
    $headers = ['Mcp-Session-Id' => mcpHttpInitialize($this, $endpoint)];
    $call = ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'craft-context-get']];

    mcpHttpRequest($this, $endpoint, 'POST', $call, $headers)->assertOk()->assertJsonPath('result.isError', false);

    app()->instance(Settings::class, new Settings(['publicEnabled' => true, 'publicTools' => []]));
    app()->forgetInstance(Access::class);

    mcpHttpRequest($this, $endpoint, 'POST', $call, $headers)
        ->assertOk()->assertJsonPath('error.message', 'Tool not found: "craft-context-get".');
});

it('keeps host checks and origin restrictions for both HTTP protocols', function (bool $modern): void {
    $payload = $modern ? McpRequest::payload('tools/list') : [
        'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize',
        'params' => ['protocolVersion' => '2025-03-26', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'Test', 'version' => '1']],
    ];
    $headers = $modern ? McpRequest::headers('tools/list') : [];

    mcpHttpRequest($this, 'http://attacker.example/_test/dual-protocol-mcp', 'POST', $payload, $headers)->assertForbidden();
    mcpHttpRequest($this, '/_test/dual-protocol-mcp', 'POST', $payload, [...$headers, 'Origin' => 'https://attacker.example'])
        ->assertForbidden()->assertHeaderMissing('Access-Control-Allow-Origin');
})->with(['handshake' => false, 'modern' => true]);
