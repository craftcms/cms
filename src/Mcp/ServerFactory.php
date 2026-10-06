<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cms;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\Stateless\StatelessProtocol;
use Psr\Log\LoggerInterface;

/**
 * @since 6.0.0
 */
readonly class ServerFactory
{
    public function __construct(
        private CapabilityDiscovery $capabilities,
        private CapabilityContainer $container,
        private LoggerInterface $logger,
        private PublicCapabilityDiscovery $publicCapabilities,
        private StdioCapabilityDiscovery $stdioCapabilities,
        private AdminInstructions $instructions,
    ) {}

    public function admin(): StatelessProtocol
    {
        return $this->builder($this->capabilities, $this->instructions->get())->buildStateless([ProtocolVersion::V2026_07_28]);
    }

    public function public(): StatelessProtocol
    {
        return $this->builder(
            discoverer: $this->publicCapabilities,
            instructions: 'Use craft-context-get to inspect allowed public content, then craft-query to query element types that the site administrator has explicitly exposed. Paginated element queries default to limit 100 and cap it at 500; use limit and offset for subsequent pages. Explicitly selected nested fields expand one level, with at most 100 nested elements across all fields of a record. Query larger nested collections separately.',
        )->buildStateless([ProtocolVersion::V2026_07_28]);
    }

    public function stdio(): Server
    {
        return $this->builder($this->stdioCapabilities, $this->instructions->get())->build();
    }

    private function builder(DiscovererInterface $discoverer, ?string $instructions = null): Builder
    {
        return new Builder()
            ->setServerInfo('Craft CMS', Cms::VERSION, 'Craft CMS MCP server')
            ->setContainer($this->container)
            ->setDiscoverer($discoverer)
            ->setInstructions($instructions)
            ->setLogger($this->logger)
            ->setDiscovery(basePath: __DIR__.'/Capabilities', scanDirs: ['.']);
    }
}
