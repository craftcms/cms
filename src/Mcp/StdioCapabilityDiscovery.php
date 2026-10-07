<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;

/**
 * @since 6.0.0
 */
readonly class StdioCapabilityDiscovery implements DiscovererInterface
{
    public function __construct(private CapabilityDiscovery $capabilities) {}

    public function discover(
        string $basePath,
        array $directories,
        array $excludeDirs = [],
        array $namePatterns = self::DEFAULT_NAME_PATERNS,
    ): DiscoveryState {
        return $this->capabilities->discoverStdio($basePath, $directories, $excludeDirs, $namePatterns);
    }
}
