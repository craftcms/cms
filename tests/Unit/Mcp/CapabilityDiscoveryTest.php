<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\CapabilityDiscovery;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Http\Request;
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

    $discovered = new CapabilityDiscovery($request, GeneralConfig::create(), $discoverer)
        ->discover(__DIR__, ['.']);

    expect(array_key_exists('plugin.example', $discovered->getTools()))->toBe($allowed);
})->with([
    'allowed' => true,
    'denied' => false,
]);
