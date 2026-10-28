<?php

namespace craft\mcp;

use Craft;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Server;
use Mcp\Server\Builder;

class ServerFactory
{
    public function __construct(
        private CapabilityDiscovery $capabilities,
        private CapabilityContainer $container,
        private PublicCapabilityDiscovery $publicCapabilities,
        private StdioCapabilityDiscovery $stdioCapabilities,
    ) {
    }

    public function admin(): Server
    {
        $scopes = Craft::$app->get('mcp')->tokenScopes ?? [];
        sort($scopes);

        return $this->builder($this->capabilities, AdminInstructions::Brief)
            ->setSession(new SessionStore(Craft::$app->getCache(), ['admin', Craft::$app->getUser()->getId(), $scopes]))
            ->build();
    }

    public function public(): Server
    {
        return $this->builder($this->publicCapabilities, 'Use craft-context-get for approved public content and supported query criteria. Then query it with craft-query.')
            ->setSession(new SessionStore(Craft::$app->getCache(), ['public']))
            ->build();
    }

    public function preflight(): Server
    {
        return $this->builder($this->capabilities)->build();
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
