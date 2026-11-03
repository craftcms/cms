<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\PublicMcp;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\CapabilityDiscovery;
use CraftCms\Cms\Mcp\CapabilityRegistry;
use CraftCms\Cms\Mcp\Events\DiscoveringCapability;
use CraftCms\Cms\Tests\Support\McpCapabilities\Example;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\AccessToken;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\ToolReference;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;

it('filters capabilities by their required Craft permissions', function (bool $allowed): void {
    $capability = new class
    {
        #[RequiresPermission('manageMyPlugin')]
        public function handle(): void {}
    };
    $state = new DiscoveryState(tools: [
        'plugin.example' => new ToolReference(
            new Tool(
                'plugin.example',
                null,
                ['type' => 'object', 'properties' => [], 'required' => null],
                null,
                null,
            ),
            $capability->handle(...),
        ),
    ]);
    $discoverer = new readonly class($state) implements DiscovererInterface
    {
        public function __construct(private DiscoveryState $state) {}

        public function discover(
            string $basePath,
            array $directories,
            array $excludeDirs = [],
            array $namePatterns = self::DEFAULT_NAME_PATERNS,
        ): DiscoveryState {
            return $this->state;
        }
    };
    $user = Mockery::mock(User::class);
    $user->expects('can')->with('manageMyPlugin')->andReturn($allowed);
    $user->allows('currentAccessToken')->andReturn(new AccessToken(['oauth_scopes' => ['mcp:write']]));
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    $discovered = new CapabilityDiscovery($request, GeneralConfig::create(), new CapabilityRegistry, $discoverer)
        ->discover(__DIR__, ['.']);

    expect(array_key_exists('plugin.example', $discovered->getTools()))->toBe($allowed);
})->with([
    'allowed' => true,
    'denied' => false,
]);

it('rejects plugin capabilities that would replace another registered capability', function (): void {
    $conflicting = new class
    {
        #[McpTool(name: 'example.greet')]
        public function replace(): string
        {
            return 'A different greeting';
        }
    };
    $registry = new CapabilityRegistry;
    $registry->register(Example::class, $conflicting::class);

    expect($registry->definitions(...))->toThrow(LogicException::class, 'Duplicate MCP capability identity [example.greet].');
});

it('lets plugins hide capabilities from users, but not approved public capabilities', function (): void {
    $capability = new class
    {
        public function handle(): void {}

        #[PublicMcp]
        public function public(): void {}
    };
    $tool = fn (string $name, Closure $handler): ToolReference => new ToolReference(
        new Tool($name, null, ['type' => 'object', 'properties' => [], 'required' => null], null, new ToolAnnotations(readOnlyHint: true)),
        $handler,
    );
    $state = new DiscoveryState(tools: [
        'plugin.read' => $tool('plugin.read', $capability->handle(...)),
        'plugin.hidden' => $tool('plugin.hidden', $capability->handle(...)),
        'plugin.public' => $tool('plugin.public', $capability->public(...)),
    ]);
    $discoverer = new readonly class($state) implements DiscovererInterface
    {
        public function __construct(private DiscoveryState $state) {}

        public function discover(
            string $basePath,
            array $directories,
            array $excludeDirs = [],
            array $namePatterns = self::DEFAULT_NAME_PATERNS,
        ): DiscoveryState {
            return $this->state;
        }
    };
    $user = Mockery::mock(User::class);
    $user->allows('currentAccessToken')->andReturn(new AccessToken(['oauth_scopes' => ['mcp:read']]));
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);
    $users = [];
    Event::listen(DiscoveringCapability::class, function (DiscoveringCapability $event) use (&$users): void {
        $users[] = $event->user;
        $event->allowed = ! ($event->reference instanceof ToolReference) || $event->reference->tool->name !== 'plugin.hidden';
    });
    Event::listen(DiscoveringCapability::class, function (DiscoveringCapability $event): void {
        if ($event->reference instanceof ToolReference && $event->reference->tool->name === 'plugin.public') {
            $event->allowed = false;
        }
    });

    $discovery = new CapabilityDiscovery($request, GeneralConfig::create(), new CapabilityRegistry, $discoverer);

    expect(array_keys($discovery->discover(__DIR__, ['.'])->getTools()))->toBe(['plugin.read'])
        ->and(array_keys($discovery->discoverWithoutToken(__DIR__, ['.'])->getTools()))->toBe(['plugin.read'])
        ->and(array_keys($discovery->discoverPublic(__DIR__, ['.'])->getTools()))->toBe(['plugin.public'])
        ->and($users)->toContain($user);
});
