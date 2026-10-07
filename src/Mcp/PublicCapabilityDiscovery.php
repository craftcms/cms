<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Mcp\Public\Access;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\PromptReference;
use Mcp\Capability\Registry\ResourceReference;
use Mcp\Capability\Registry\ResourceTemplateReference;
use Mcp\Capability\Registry\ToolReference;

/**
 * @since 6.0.0
 */
readonly class PublicCapabilityDiscovery implements DiscovererInterface
{
    public function __construct(
        private CapabilityDiscovery $capabilities,
        private Access $access,
    ) {}

    public function discover(
        string $basePath,
        array $directories,
        array $excludeDirs = [],
        array $namePatterns = self::DEFAULT_NAME_PATERNS,
    ): DiscoveryState {
        $discovered = $this->capabilities->discoverPublic($basePath, $directories, $excludeDirs, $namePatterns);

        return new DiscoveryState(
            tools: array_filter(
                $discovered->getTools(),
                fn (ToolReference $reference): bool => $this->access->approved('tools', $reference->tool->name),
            ),
            resources: array_filter(
                $discovered->getResources(),
                fn (ResourceReference $reference): bool => $this->access->approved('resources', $reference->resource->uri),
            ),
            prompts: array_filter(
                $discovered->getPrompts(),
                fn (PromptReference $reference): bool => $this->access->approved('prompts', $reference->prompt->name),
            ),
            resourceTemplates: array_filter(
                $discovered->getResourceTemplates(),
                fn (ResourceTemplateReference $reference): bool => $this->access->approved('resourceTemplates', $reference->resourceTemplate->uriTemplate),
            ),
        );
    }
}
