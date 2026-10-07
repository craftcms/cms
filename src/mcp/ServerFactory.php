<?php

namespace craft\mcp;

use Craft;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\Stateless\StatelessProtocol;

class ServerFactory
{
    public function __construct(
        private CapabilityDiscovery $capabilities,
        private CapabilityContainer $container,
        private PublicCapabilityDiscovery $publicCapabilities,
        private StdioCapabilityDiscovery $stdioCapabilities,
    ) {
    }

    public function admin(): StatelessProtocol
    {
        return $this->builder($this->capabilities, AdminInstructions::Brief)->buildStateless([ProtocolVersion::V2026_07_28]);
    }

    public function public(): StatelessProtocol
    {
        return $this->builder($this->publicCapabilities, 'Use craft-context-get for approved public content and supported query criteria. Then query it with craft-query.')->buildStateless([ProtocolVersion::V2026_07_28]);
    }

    public function preflight(): StatelessProtocol
    {
        return $this->builder($this->capabilities)->buildStateless([ProtocolVersion::V2026_07_28]);
    }

    public function stdio(): Server
    {
        return $this->builder($this->stdioCapabilities, AdminInstructions::Brief)->build();
    }

    private function builder(DiscovererInterface $discoverer, ?string $instructions = null): Builder
    {
        return (new Builder())
            ->setServerInfo('Craft CMS', Craft::$app->getVersion(), 'Craft CMS MCP server')
            ->setContainer($this->container)
            ->setDiscoverer($discoverer)
            ->setInstructions($instructions)
            ->setLogger(new Logger())
            ->setDiscovery(basePath: __DIR__ . '/capabilities', scanDirs: ['.']);
    }
}
