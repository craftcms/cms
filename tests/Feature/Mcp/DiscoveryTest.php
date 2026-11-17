<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Mcp\RouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Testing\TestResponse;

function registerMcpDiscovery(array $settings, bool $registration = false): Settings
{
    app(ProjectConfig::class)->set('users.allowPublicRegistration', $registration);
    app()->instance(Settings::class, $instance = new Settings($settings));
    app(RouteRegistrar::class)->register();

    return $instance;
}

function mcpDiscoveryName(): string
{
    return Str::slug(Cms::systemName()) ?: 'craft';
}

function assertMcpDiscoveryHeaders(TestResponse $response, string $contentType): void
{
    $response
        ->assertHeader('Content-Type', $contentType)
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertHeader('Access-Control-Allow-Methods', 'GET')
        ->assertHeader('Access-Control-Expose-Headers', 'ETag');

    expect($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=3600');
}

const MCP_BOTH_SERVERS = ['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/mcp'];

it('advertises the public server', function (): void {
    registerMcpDiscovery(MCP_BOTH_SERVERS);
    $name = mcpDiscoveryName();

    $catalog = $this->get('/.well-known/ai-catalog.json')->assertOk();
    assertMcpDiscoveryHeaders($catalog, 'application/ai-catalog+json');
    expect($catalog->json())->toBe([
        'specVersion' => '1.0',
        'entries' => [
            [
                'identifier' => "urn:air:localhost:mcp:$name",
                'type' => 'application/mcp-server-card+json',
                'url' => 'http://localhost/mcp/server-card',
            ],
        ],
    ]);

    $card = $this->get('/mcp/server-card')->assertOk();
    assertMcpDiscoveryHeaders($card, 'application/mcp-server-card+json');
    expect($card->json())->toBe([
        '$schema' => 'https://static.modelcontextprotocol.io/schemas/v1/server-card.schema.json',
        'name' => "localhost/$name",
        'version' => explode('.', Cms::version())[0],
        'description' => 'Craft CMS MCP server',
        'title' => Cms::systemName(),
        'remotes' => [['type' => 'streamable-http', 'url' => 'http://localhost/mcp']],
    ]);

    // Without front-end registration, the authenticated server doesn't get a card
    $this->get('/mcp/auth/server-card')->assertNotFound();
});

it('validates Server Cards with entity tags', function (): void {
    registerMcpDiscovery(['publicEnabled' => true, 'publicRoute' => '/mcp']);

    $etag = $this->get('/mcp/server-card')->headers->get('ETag');
    expect($etag)->toMatch('/^"[0-9a-f]{40}"$/');

    $this->get('/mcp/server-card', ['If-None-Match' => "\"stale\", $etag"])
        ->assertStatus(304)
        ->assertContent('');
    $this->get('/mcp/server-card', ['If-None-Match' => '"stale"'])->assertOk();
});

it('answers Server Card preflight requests', function (): void {
    registerMcpDiscovery(['publicEnabled' => true, 'publicRoute' => '/mcp']);

    $this->call('OPTIONS', '/mcp/server-card')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertHeader('Access-Control-Allow-Headers', 'Content-Type, If-None-Match');
});

it('advertises the authenticated server only with public registration', function (): void {
    registerMcpDiscovery(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth']);
    $this->get('/.well-known/ai-catalog.json')->assertNotFound();

    registerMcpDiscovery(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth'], registration: true);
    $entries = $this->get('/.well-known/ai-catalog.json')->assertOk()->json('entries');
    expect(array_column($entries, 'url'))->toBe(['http://localhost/mcp/auth/server-card'])
        ->and($entries[0]['identifier'])->toEndWith(':mcp:'.mcpDiscoveryName().'-admin');

    // The card points OAuth clients at the authorization endpoint
    $card = $this->get('/mcp/auth/server-card')->assertOk()->json();
    expect($card['name'])->toEndWith('/'.mcpDiscoveryName().'-admin')
        ->and($card['remotes'])->toBe([['type' => 'streamable-http', 'url' => 'http://localhost/mcp/auth']]);
});

it('advertises both servers', function (): void {
    registerMcpDiscovery(MCP_BOTH_SERVERS, registration: true);

    expect(array_column($this->get('/.well-known/ai-catalog.json')->json('entries'), 'url'))
        ->toBe(['http://localhost/mcp/server-card', 'http://localhost/mcp/auth/server-card']);
});

it('advertises a shared endpoint once, as the public server', function (): void {
    // Requests without credentials reach the public server at a shared endpoint
    registerMcpDiscovery([...MCP_BOTH_SERVERS, 'authorizationRoute' => '/mcp'], registration: true);

    $entries = $this->get('/.well-known/ai-catalog.json')->json('entries');
    expect($entries)->toHaveCount(1)
        ->and($entries[0]['identifier'])->toEndWith(':mcp:'.mcpDiscoveryName());
});

it('does not advertise disabled servers', function (): void {
    registerMcpDiscovery(['enabled' => false, 'publicEnabled' => false], registration: true);

    $this->get('/.well-known/ai-catalog.json')->assertNotFound();
    $this->get('/mcp/server-card')->assertNotFound();
    $this->get('/mcp/auth/server-card')->assertNotFound();
});

it('does not advertise endpoints that conflict with other routes', function (): void {
    // The endpoint isn't registered, so advertising it would point clients at another route
    $settings = registerMcpDiscovery(['publicEnabled' => true, 'publicRoute' => '/.well-known/passkey-endpoints']);

    expect($settings->discoverableServers())->toBe([]);
    $this->get('/.well-known/ai-catalog.json')->assertNotFound();
});

it('does not advertise control panel endpoints', function (): void {
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
    registerMcpDiscovery(['enabled' => true, 'route' => "/$cpTrigger/mcp", 'authorizationRoute' => "/$cpTrigger/mcp/auth"], registration: true);

    $this->get('/.well-known/ai-catalog.json')->assertNotFound();
    $this->get("/$cpTrigger/mcp/auth/server-card")->assertNotFound();
});

it('does not advertise anything when the control panel has its own domain', function (): void {
    // Without a trigger, every request is a control panel request
    $cpTrigger = Cms::config()->cpTrigger;
    Cms::config()->cpTrigger = null;

    try {
        expect(new Settings(MCP_BOTH_SERVERS)->discoverableServers())->toBe([]);
    } finally {
        Cms::config()->cpTrigger = $cpTrigger;
    }

    expect(new Settings(MCP_BOTH_SERVERS)->discoverableServers())->toBe(['mcp' => true]);
});

it('rate-limits discovery requests', function (): void {
    Cms::config()->defaultRateLimit = 1;
    registerMcpDiscovery(['publicEnabled' => true, 'publicRoute' => '/mcp']);

    $this->get('/mcp/server-card')->assertOk();
    $this->get('/mcp/server-card')->assertTooManyRequests();

    // Each route is tracked separately
    $this->get('/.well-known/ai-catalog.json')->assertOk();
    $this->get('/.well-known/ai-catalog.json')->assertTooManyRequests();

    // Disabling the default rate limit lifts it
    Cms::config()->defaultRateLimit = false;
    $this->get('/mcp/server-card')->assertOk();
});
