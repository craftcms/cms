<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Settings\SettingsIndexController;
use CraftCms\Cms\Mcp\CapabilityRegistry;
use CraftCms\Cms\Mcp\Public\Access as PublicMcpAccess;
use CraftCms\Cms\Mcp\RouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\McpCapabilities\Example;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Laravel\Passport\Passport;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

it('exposes the authenticated MCP connection settings', function () {
    actingAs(User::find()->one());

    get(action(SettingsIndexController::class))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settings.Extend.mcp', [
                'label' => 'MCP',
                'url' => route('craft.cp.settings.mcp.index'),
                'iconName' => 'light/robot',
            ]))
        ->assertOk();

    get(route('craft.cp.settings.mcp.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/mcp/Settings')
            ->where('ui.values.enabled', true)
            ->where('ui.values.route', '/mcp')
            ->where('ui.values.authorizationRoute', '/mcp/auth')
            ->where('ui.values.publicEnabled', false)
            ->where('ui.values.publicRoute', '/mcp')
            ->where('refreshUrl', route('craft.cp.settings.mcp.render-ui')))
        ->assertOk();
});

it('notes control panel endpoints and endpoints both servers share', function (array $values, array $notices): void {
    actingAs(User::find()->one());
    $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');
    $values = array_map(fn (mixed $value): mixed => is_string($value) ? str_replace('{cp}', $cpTrigger, $value) : $value, $values);

    $nodes = postJson(route('craft.cp.settings.mcp.render-ui'), ['values' => $values, 'scope' => []])
        ->assertOk()
        ->json('ui.nodes');

    expect(collect($nodes)->where('component', 'craft:callout')->pluck('uid')->values()->all())->toBe($notices);
})->with([
    'separate site endpoints' => [['enabled' => true, 'route' => '/mcp', 'publicEnabled' => true, 'publicRoute' => '/public-mcp'], []],
    'control panel endpoint' => [['enabled' => true, 'route' => '/{cp}/mcp', 'publicEnabled' => false, 'publicRoute' => '/mcp'], ['mcp-route-cp-notice']],
    'shared endpoint' => [['enabled' => true, 'route' => '/mcp/', 'publicEnabled' => true, 'publicRoute' => '/mcp'], ['mcp-shared-endpoint-warning']],
    'shared endpoint with a disabled server' => [['enabled' => true, 'route' => '/mcp', 'publicEnabled' => false, 'publicRoute' => '/mcp'], []],
]);

it('sends requests to a shared endpoint by whether they carry a bearer token', function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    openssl_pkey_export($key, $privateKey);
    config()->set('passport.private_key', $privateKey);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    enableMcp(['publicEnabled' => true, 'publicRoute' => '/mcp', 'publicTools' => ['craft-context-get']]);
    $tools = fn (array $headers = []): array => array_column(
        postJson('/mcp', McpRequest::payload('tools/list'), [...McpRequest::headers('tools/list'), ...$headers])->assertOk()->json('result.tools'),
        'name',
    );

    expect($tools())->toBe(['craft-context-get']);

    $this->app['auth']->forgetGuards();
    postJson('/mcp', McpRequest::payload('tools/list'), [...McpRequest::headers('tools/list'), 'Authorization' => 'Bearer nope'])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');

    // The authorization endpoint always asks for a token, so clients can discover how to sign in
    postJson('/mcp/auth', McpRequest::payload('tools/list'), McpRequest::headers('tools/list'))->assertUnauthorized();

    Passport::actingAs(UserModel::query()->firstOrFail(), ['mcp:read'], 'craft-mcp');
    Route::getRoutes()->getByName('craft.mcp.server')->flushController();
    expect($tools(['Authorization' => 'Bearer token']))->toContain('elements.list')->not->toContain('craft-context-get');
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
    app(RouteRegistrar::class)->register();

    postJson($route, McpRequest::payload('tools/list'), McpRequest::headers('tools/list'))
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
    app(RouteRegistrar::class)->register();

    postJson(
        $route,
        McpRequest::payload('tools/call', [
            'name' => 'craft-query',
            'arguments' => ['type' => 'user', 'criteria' => ['limit' => 1]],
        ]),
        McpRequest::headers('tools/call', 'craft-query'),
    )
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.type', 'user')
        ->assertJsonPath('result.structuredContent.limit', 1)
        ->assertJsonCount(1, 'result.structuredContent.elements');
});

it('discovers plugin capabilities in settings and requires approval before serving them', function (
    string $property,
    string $identity,
    string $label,
    string $method,
    array $params,
    string $resultPath,
    mixed $expected,
): void {
    $capabilities = app(CapabilityRegistry::class);
    $capabilities->register(Example::class);
    $capabilities->register(Example::class);
    actingAs(User::find()->one());
    $settings = new Settings(['publicEnabled' => true, 'publicRoute' => '/_test/plugin-mcp']);

    get(route('craft.cp.settings.mcp.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes', function ($nodes) use ($property, $identity, $label): bool {
                $field = collect($nodes)->firstWhere('control.path', [$property]);
                $permissions = $field['control']['props']['groups'][0]['permissions'] ?? [];

                return ($permissions[$identity]['label'] ?? null) === $label
                    && ! isset($permissions['example.manage']);
            })
            ->where("ui.values.$property", []));

    post(route('craft.cp.settings.mcp.store'), $settings->toArray())
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    app()->forgetInstance(Settings::class);
    app(RouteRegistrar::class)->register();

    postJson($settings->publicRoute, McpRequest::payload($method, $params), McpRequest::headers($method, $params['name'] ?? $params['uri'] ?? null))->assertBadRequest()
        ->assertJsonPath('error.code', -32602);
    $publicRoute = Route::current();

    $settings->$property = [$identity];
    post(route('craft.cp.settings.mcp.store'), $settings->toArray())
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    app()->forgetInstance(Settings::class);
    $publicRoute->flushController();

    postJson($settings->publicRoute, McpRequest::payload($method, $params), McpRequest::headers($method, $params['name'] ?? $params['uri'] ?? null))
        ->assertOk()
        ->assertJsonMissingPath('error')
        ->assertJsonPath($resultPath, $expected);

    $settings->$property = [];
    post(route('craft.cp.settings.mcp.store'), $settings->toArray())
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    app()->forgetInstance(Settings::class);
    $publicRoute->flushController();

    postJson($settings->publicRoute, McpRequest::payload($method, $params), McpRequest::headers($method, $params['name'] ?? $params['uri'] ?? null))->assertBadRequest()
        ->assertJsonPath('error.code', -32602);
})->with([
    'tool' => ['publicTools', 'example.greet', 'Greet a visitor', 'tools/call', ['name' => 'example.greet', 'arguments' => ['name' => 'Ada']], 'result.structuredContent.greeting', 'Hello, Ada!'],
    'resource' => ['publicResources', 'example://welcome', 'Welcome message', 'resources/read', ['uri' => 'example://welcome'], 'result.contents.0.text', 'Welcome to the example plugin'],
    'resource template' => ['publicResourceTemplates', 'example://visitors/{name}', 'Visitor greeting', 'resources/read', ['uri' => 'example://visitors/Ada'], 'result.contents.0.text', 'Welcome, Ada'],
    'prompt' => ['publicPrompts', 'example-introduce', 'Introduce a visitor', 'prompts/get', ['name' => 'example-introduce', 'arguments' => ['name' => 'Ada']], 'result.messages.0.content.text', 'Introduce Ada'],
]);

it('applies Craft permissions to plugin capabilities on the authenticated server', function (): void {
    app(CapabilityRegistry::class)->register(Example::class);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $user = UserModel::query()->firstOrFail();
    Passport::actingAs($user, ['mcp:admin'], 'craft-mcp');

    postJson(route('craft.mcp.server'), McpRequest::payload('tools/call', ['name' => 'example.manage']), McpRequest::headers('tools/call', 'example.manage'))
        ->assertOk()
        ->assertJsonPath('result.content.0.text', 'Managed example');

    Route::getRoutes()->getByName('craft.mcp.server')->flushController();
    postJson(route('craft.mcp.server'), McpRequest::payload('tools/call', ['name' => 'example.greet', 'arguments' => ['name' => 'Ada']]), McpRequest::headers('tools/call', 'example.greet'))->assertBadRequest()
        ->assertJsonPath('error.code', -32602);

    Edition::set(Edition::Pro);
    $user->admin = false;
    app(UserPermissions::class)->saveUserPermissions($user->id, ['accessCp']);

    Route::getRoutes()->getByName('craft.mcp.server')->flushController();
    postJson(route('craft.mcp.server'), McpRequest::payload('tools/call', ['name' => 'example.manage']), McpRequest::headers('tools/call', 'example.manage'))->assertBadRequest()
        ->assertJsonPath('error.code', -32602);
});
