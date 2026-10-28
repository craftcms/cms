<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Mcp\Enums\McpScope;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Laravel\Passport\Contracts\OAuthenticatable;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\Session\Psr16SessionStore;
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
        private Repository $cache,
        private Request $request,
    ) {}

    public function admin(): Server
    {
        $user = $this->request->user('craft-mcp');
        assert($user instanceof OAuthenticatable);
        $scopes = array_filter(McpScope::values(), $user->tokenCan(...));
        $identity = hash('sha256', json_encode([$user->getAuthIdentifier(), $scopes], JSON_THROW_ON_ERROR));

        return $this->builder($this->capabilities, AdminInstructions::Brief)
            ->setSession(new Psr16SessionStore($this->cache, "craft-mcp-admin-$identity-"))
            ->build();
    }

    public function public(): Server
    {
        return $this->builder(
            discoverer: $this->publicCapabilities,
            instructions: 'Use craft-context-get to inspect allowed public content, then craft-query to query element types that the site administrator has explicitly exposed. Paginated element queries default to limit 100 and cap it at 500; use limit and offset for subsequent pages. Explicitly selected nested fields expand one level, with at most 100 nested elements across all fields of a record. Query larger nested collections separately.',
        )->setSession(new Psr16SessionStore($this->cache, 'craft-mcp-public-'))->build();
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
        return new Builder()
            ->setServerInfo('Craft CMS', Cms::VERSION, 'Craft CMS MCP server')
            ->setContainer($this->container)
            ->setDiscoverer($discoverer)
            ->setInstructions($instructions)
            ->setLogger($this->logger)
            ->setDiscovery(basePath: __DIR__.'/Capabilities', scanDirs: ['.']);
    }
}
