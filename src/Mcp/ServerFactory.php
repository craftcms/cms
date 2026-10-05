<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cms;
use Illuminate\Container\Container;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server\Builder;
use Mcp\Server\Stateless\StatelessProtocol;

/**
 * @since 6.0.0
 */
readonly class ServerFactory
{
    public function __construct(
        private Container $app,
        private CapabilityDiscovery $capabilities,
    ) {}

    public function admin(): StatelessProtocol
    {
        return $this->make(['.'], ['Public']);
    }

    public function public(): StatelessProtocol
    {
        return $this->make(['Public']);
    }

    /**
     * @param  list<string>  $scanDirectories
     * @param  list<string>  $excludeDirectories
     */
    private function make(array $scanDirectories, array $excludeDirectories = []): StatelessProtocol
    {
        return new Builder()
            ->setServerInfo('Craft CMS', Cms::VERSION, 'Craft CMS MCP server')
            ->setContainer($this->app)
            ->setDiscoverer($this->capabilities)
            ->setDiscovery(
                basePath: __DIR__.'/Capabilities',
                scanDirs: $scanDirectories,
                excludeDirs: $excludeDirectories,
            )
            ->buildStateless([ProtocolVersion::V2026_07_28]);
    }
}
