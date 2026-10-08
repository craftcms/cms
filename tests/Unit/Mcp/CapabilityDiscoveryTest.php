<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\CapabilityDiscovery;
use CraftCms\Cms\Mcp\CapabilityRegistry;
use CraftCms\Cms\Tests\Support\McpCapabilities\Example;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Http\Request;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\ToolReference;
use Mcp\Schema\Tool;

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
    $user = Mockery::mock(CraftUser::class);
    $user->expects('can')->with('manageMyPlugin')->andReturn($allowed);
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
