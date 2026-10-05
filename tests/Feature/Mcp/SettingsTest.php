<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Http\Controllers\Settings\SettingsIndexController;
use CraftCms\Cms\Mcp\Public\Access as PublicMcpAccess;
use CraftCms\Cms\Mcp\PublicRouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;
use Mcp\Schema\Wire\McpHeader;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

it('exposes the authenticated MCP connection settings', function () {
    actingAs(User::find()->one());

    get(action(SettingsIndexController::class))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settings.System.mcp', [
                'label' => 'MCP',
                'url' => route('craft.cp.settings.mcp.index'),
                'iconName' => 'light/robot',
            ]))
        ->assertOk();

    get(route('craft.cp.settings.mcp.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Form')
            ->where('form.values.endpoint', route('craft.cp.mcp.server'))
            ->where('form.values.publicEnabled', false)
            ->where('form.values.publicRoute', '/mcp'))
        ->assertOk();
});

it('stores public MCP settings in project config', function (): void {
    actingAs(User::find()->one());
    $siteHandle = Sites::getPrimarySite()->handle;
    $projectConfig = app(ProjectConfig::class);

    post(route('craft.cp.settings.mcp.store'), new Settings([
        'publicEnabled' => true,
        'publicRoute' => '/services/mcp',
        'publicElementTypes' => [Address::class, 'user'],
        'publicSiteHandles' => [$siteHandle],
        'publicTools' => ['craft-context-get', 'craft-query'],
    ])->toArray())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($projectConfig->get('mcp.publicEnabled'))->toBeTrue()
        ->and($projectConfig->get('mcp.publicRoute'))->toBe('/services/mcp')
        ->and($projectConfig->get('mcp.publicElementTypes'))->toBe([Address::class, 'user'])
        ->and($projectConfig->get('mcp.publicSiteHandles'))->toBe([$siteHandle])
        ->and($projectConfig->get('mcp.publicTools'))->toBe(['craft-context-get', 'craft-query']);
});

it('serves only approved tools from the configured public endpoint', function (): void {
    $route = '/_test/public-mcp';
    app()->instance(Settings::class, new Settings([
        'publicEnabled' => true,
        'publicRoute' => $route,
        'publicSiteHandles' => [Sites::getPrimarySite()->handle],
        'publicTools' => ['craft-context-get'],
    ]));
    app()->forgetInstance(PublicMcpAccess::class);
    app(PublicRouteRegistrar::class)->register();

    postJson($route, publicMcpPayload('tools/list'), publicMcpHeaders('tools/list'))
        ->assertOk()
        ->assertJsonCount(1, 'result.tools')
        ->assertJsonPath('result.tools.0.name', 'craft-context-get');

});

it('queries only explicitly exposed public element types', function (): void {
    $route = '/_test/public-user-mcp';
    app()->instance(Settings::class, new Settings([
        'publicEnabled' => true,
        'publicRoute' => $route,
        'publicSiteHandles' => [Sites::getPrimarySite()->handle],
        'publicElementTypes' => ['user'],
        'publicTools' => ['craft-query'],
    ]));
    app()->forgetInstance(PublicMcpAccess::class);
    app(PublicRouteRegistrar::class)->register();

    postJson(
        $route,
        publicMcpPayload('tools/call', [
            'name' => 'craft-query',
            'arguments' => ['type' => 'user', 'criteria' => ['limit' => 1]],
        ]),
        publicMcpHeaders('tools/call', 'craft-query'),
    )
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.type', 'user')
        ->assertJsonPath('result.structuredContent.limit', 1)
        ->assertJsonCount(1, 'result.structuredContent.elements');
});

/** @param array<string, mixed> $params @return array<string, mixed> */
function publicMcpPayload(string $method, array $params = []): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => 'public-mcp-test',
        'method' => $method,
        'params' => [
            ...$params,
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
            ],
        ],
    ];
}

/** @return array<string, string> */
function publicMcpHeaders(string $method, ?string $name = null): array
{
    return array_filter([
        McpHeader::PROTOCOL_VERSION => '2026-07-28',
        McpHeader::METHOD => $method,
        McpHeader::NAME => $name,
    ], static fn (?string $value): bool => $value !== null);
}
