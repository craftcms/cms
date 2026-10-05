<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use Illuminate\Http\Request;
use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\ElementReference;
use ReflectionMethod;

/**
 * @since 6.0.0
 */
readonly class CapabilityDiscovery implements DiscovererInterface
{
    private DiscovererInterface $discoverer;

    public function __construct(
        private Request $request,
        private GeneralConfig $config,
        ?DiscovererInterface $discoverer = null,
    ) {
        $this->discoverer = $discoverer ?? new Discoverer;
    }

    public function discover(
        string $basePath,
        array $directories,
        array $excludeDirs = [],
        array $namePatterns = self::DEFAULT_NAME_PATERNS,
    ): DiscoveryState {
        $discovered = $this->discoverer->discover($basePath, $directories, $excludeDirs, $namePatterns);

        return new DiscoveryState(
            tools: array_filter($discovered->getTools(), $this->allows(...)),
            resources: array_filter($discovered->getResources(), $this->allows(...)),
            prompts: array_filter($discovered->getPrompts(), $this->allows(...)),
            resourceTemplates: array_filter($discovered->getResourceTemplates(), $this->allows(...)),
        );
    }

    private function allows(ElementReference $reference): bool
    {
        $method = $this->reflection($reference->handler);
        $requiresAdminChanges = ($method?->getAttributes(RequiresAdminChanges::class) ?? []) !== [];
        $requiresAdmin = $requiresAdminChanges || ($method?->getAttributes(RequiresAdmin::class) ?? []) !== [];

        if (! $requiresAdmin) {
            return true;
        }

        $user = $this->request->craftUser();

        return $user?->isAdmin() === true
            && (! $requiresAdminChanges || $this->config->allowAdminChanges);
    }

    /**
     * @param  Closure|array{0: object|string, 1: string}|string  $handler
     */
    private function reflection(Closure|array|string $handler): ?ReflectionMethod
    {
        if (! is_array($handler)) {
            return null;
        }

        return new ReflectionMethod($handler[0], $handler[1]);
    }
}
