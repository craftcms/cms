<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\config\GeneralConfig;
use craft\db\Connection;
use craft\elements\User;
use craft\mcp\AdminInstructions;
use craft\mcp\attributes\PublicMcp;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\mcp\attributes\RequiresPermission;
use craft\mcp\CapabilityDiscovery;
use craft\mcp\CapabilityRegistry;
use craft\mcp\EditionFeatures;
use craft\mcp\ElementQueryFactory;
use craft\mcp\events\CollectingServerInstructions;
use craft\mcp\events\DiscoveringCapability;
use craft\mcp\public\Access;
use craft\mcp\PublicCapabilityDiscovery;
use craft\mcp\PublicElementTypes;
use craft\mcp\Settings;
use craft\oauth\Resource;
use craft\services\Elements;
use craft\services\Mcp as McpService;
use craft\services\OAuth;
use craft\services\Users;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\ToolReference;
use Mcp\Schema\ToolAnnotations;
use PHPUnit\Framework\TestCase;
use yii\base\Event;
use yii\console\Application;

/** Plugin capabilities must follow the same discovery gates as core capabilities. */
class CapabilityDiscoveryTest extends TestCase
{
    private mixed $originalApp;
    private array $aliases;
    private string $timezone;
    private CapabilityDiscovery $discovery;
    private GeneralConfig $config;
    private DiscoveryTestApplication $app;

    protected function setUp(): void
    {
        $this->originalApp = Craft::$app;
        $this->aliases = Craft::$aliases;
        $this->timezone = date_default_timezone_get();
        $this->app = new DiscoveryTestApplication(['id' => 'discovery-test', 'basePath' => dirname(__DIR__, 3) . '/src']);
        $this->app->identity = new DiscoveryTestUser(['active' => true]);
        $this->app->set('mcp', new McpService(['tokenScopes' => [McpService::SCOPE_ADMIN]]));
        $this->app->set('oauth', new class() extends OAuth {
            public function getResource(string $id): Resource
            {
                return (new McpService())->getOAuthResource();
            }
        });
        $this->config = new GeneralConfig(['allowAdminChanges' => false]);
        $registry = new CapabilityRegistry([PluginTools::class]);
        $empty = new class() implements DiscovererInterface {
            public function discover(string $basePath, array $directories, array $excludeDirs = [], array $namePatterns = self::DEFAULT_NAME_PATERNS): DiscoveryState
            {
                return new DiscoveryState();
            }
        };
        $this->discovery = new CapabilityDiscovery($this->config, $registry, $empty);
    }

    protected function tearDown(): void
    {
        Event::off(McpService::class, McpService::EVENT_DISCOVERING_CAPABILITY);
        Event::off(AdminInstructions::class, AdminInstructions::EVENT_COLLECT_SERVER_INSTRUCTIONS);
        Craft::$app = $this->originalApp;
        Craft::$aliases = $this->aliases;
        date_default_timezone_set($this->timezone);
    }

    public function testPermissionsAndAdminChangesControlPluginDiscovery(): void
    {
        self::assertSame(['plugin.read'], $this->toolNames($this->discovery->discover(__DIR__, [])));

        $this->app->identity->allowed = ['editContent'];
        self::assertSame(['plugin.edit', 'plugin.read'], $this->toolNames($this->discovery->discover(__DIR__, [])));

        $this->app->identity->admin = true;
        self::assertSame(['plugin.edit', 'plugin.read'], $this->toolNames($this->discovery->discover(__DIR__, [])));

        $this->config->allowAdminChanges = true;
        self::assertSame(['plugin.configure', 'plugin.edit', 'plugin.read'], $this->toolNames($this->discovery->discover(__DIR__, [])));
    }

    /**
     * @dataProvider tokenScopeProvider
     */
    public function testTokenScopeControlsDiscovery(?array $scopes, array $expected): void
    {
        $this->app->identity->admin = true;
        $this->config->allowAdminChanges = true;
        Craft::$app->get('mcp')->tokenScopes = $scopes;

        self::assertSame($expected, $this->toolNames($this->discovery->discover(__DIR__, [])));
        self::assertSame(['plugin.configure', 'plugin.edit', 'plugin.read'], $this->toolNames($this->discovery->discoverStdio(__DIR__, [])));
    }

    public static function tokenScopeProvider(): array
    {
        return [
            'no token' => [null, []],
            'read' => [[McpService::SCOPE_READ], ['plugin.read']],
            'write' => [[McpService::SCOPE_READ, McpService::SCOPE_WRITE], ['plugin.edit', 'plugin.read']],
            'write alone' => [[McpService::SCOPE_WRITE], ['plugin.edit', 'plugin.read']],
            'admin' => [[McpService::SCOPE_READ, McpService::SCOPE_WRITE, McpService::SCOPE_ADMIN], ['plugin.configure', 'plugin.edit', 'plugin.read']],
        ];
    }

    public function testPublicPluginToolRequiresExplicitApproval(): void
    {
        $settings = new Settings(['publicEnabled' => true]);
        $access = new Access($settings, new PublicElementTypes(new ElementQueryFactory(new Elements(['bulkOpDb' => new Connection()]))));
        $public = new PublicCapabilityDiscovery($this->discovery, $access);
        self::assertSame([], $this->toolNames($public->discover(__DIR__, [])));

        $settings->publicTools = ['plugin.public'];
        self::assertSame(['plugin.public'], $this->toolNames($public->discover(__DIR__, [])));
        self::assertNotContains('plugin.public', $this->toolNames($this->discovery->discover(__DIR__, [])));
    }

    public function testPluginsCanHideCapabilitiesFromUsers(): void
    {
        $this->app->identity->allowed = ['editContent'];
        $users = [];
        Event::on(McpService::class, McpService::EVENT_DISCOVERING_CAPABILITY, function(DiscoveringCapability $event) use (&$users) {
            $users[] = $event->user;
            if ($event->reference instanceof ToolReference && $event->reference->tool->name === 'plugin.edit' && !$event->user?->can('accessCp')) {
                $event->allowed = false;
            }
        });

        self::assertSame(['plugin.read'], $this->toolNames($this->discovery->discover(__DIR__, [])));
        self::assertSame(['plugin.read'], $this->toolNames($this->discovery->discoverStdio(__DIR__, [])));
        self::assertContains($this->app->identity, $users);

        $this->app->identity->allowed[] = 'accessCp';
        self::assertSame(['plugin.edit', 'plugin.read'], $this->toolNames($this->discovery->discover(__DIR__, [])));
        self::assertSame(['plugin.edit', 'plugin.read'], $this->toolNames($this->discovery->discoverStdio(__DIR__, [])));
    }

    public function testPluginsCannotHideApprovedPublicCapabilities(): void
    {
        Event::on(McpService::class, McpService::EVENT_DISCOVERING_CAPABILITY, function(DiscoveringCapability $event) {
            $event->allowed = false;
        });
        $settings = new Settings(['publicEnabled' => true, 'publicTools' => ['plugin.public']]);
        $access = new Access($settings, new PublicElementTypes(new ElementQueryFactory(new Elements(['bulkOpDb' => new Connection()]))));

        self::assertSame(['plugin.public'], $this->toolNames((new PublicCapabilityDiscovery($this->discovery, $access))->discover(__DIR__, [])));
    }

    public function testServerInstructionsOnlyPointToInfoGetForControlPanelUsers(): void
    {
        $instructions = new AdminInstructions(
            new ElementQueryFactory(new Elements(['bulkOpDb' => new Connection()])),
            new EditionFeatures(new Users()),
        );
        Event::on(AdminInstructions::class, AdminInstructions::EVENT_COLLECT_SERVER_INSTRUCTIONS, function(CollectingServerInstructions $event) {
            $event->instructions[] = 'Start with account.get.';
        });

        $brief = $instructions->brief();
        self::assertStringNotContainsString('info.get', $brief);
        self::assertStringEndsWith("\n\nStart with account.get.", $brief);

        $this->app->identity->allowed = ['accessCp'];
        $brief = $instructions->brief();
        self::assertStringStartsWith('Call info.get', $brief);
        self::assertStringEndsWith("\n\nStart with account.get.", $brief);
    }

    private function toolNames(DiscoveryState $state): array
    {
        $names = array_map(static fn($reference) => $reference->tool->name, array_values($state->getTools()));
        sort($names);
        return $names;
    }
}

class DiscoveryTestApplication extends Application
{
    public DiscoveryTestUser $identity;

    public function getConfig(): \craft\services\Config
    {
        return new \craft\services\Config();
    }

    public function getIsInstalled(): bool
    {
        return false;
    }

    public function getUser(): object
    {
        return new class($this->identity) {
            public function __construct(private User $identity)
            {
            }
            public function getIdentity(): User
            {
                return $this->identity;
            }
        };
    }
}

class DiscoveryTestUser extends User
{
    public array $allowed = [];

    public function __construct($config = [])
    {
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }
    }

    public function can(string $permission): bool
    {
        return $this->admin || in_array($permission, $this->allowed, true);
    }
}

class PluginTools
{
    #[McpTool(name: 'plugin.read', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function read(): string
    {
        return 'read';
    }

    #[McpTool(name: 'plugin.edit')]
    #[RequiresPermission('editContent')]
    public function edit(): string
    {
        return 'edited';
    }

    #[McpTool(name: 'plugin.configure')]
    #[RequiresAdminChanges]
    public function configure(): string
    {
        return 'configured';
    }

    #[McpTool(name: 'plugin.public')]
    #[PublicMcp]
    public function publicInfo(): string
    {
        return 'public';
    }
}
