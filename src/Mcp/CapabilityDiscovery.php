<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\PublicMcp;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\Attributes\RequiresHttp;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use Illuminate\Http\Request;
use LogicException;
use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\ElementReference;
use ReflectionFunction;
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
        private CapabilityRegistry $registry,
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
        $discovered = $this->discoverAll($basePath, $directories, $excludeDirs, $namePatterns);

        return $this->filter(
            $discovered,
            fn (ElementReference $reference): bool => ! $this->isPublic($reference) && $this->allows($reference),
        );
    }

    /**
     * @param  list<string>  $directories
     * @param  list<string>  $excludeDirs
     * @param  list<string>  $namePatterns
     */
    public function discoverPublic(
        string $basePath,
        array $directories,
        array $excludeDirs = [],
        array $namePatterns = self::DEFAULT_NAME_PATERNS,
    ): DiscoveryState {
        $discovered = $this->discoverAll($basePath, $directories, $excludeDirs, $namePatterns);

        return $this->filter(
            $discovered,
            fn (ElementReference $reference): bool => $this->isPublic($reference) && $this->allows($reference),
        );
    }

    /**
     * @param  list<string>  $directories
     * @param  list<string>  $excludeDirs
     * @param  list<string>  $namePatterns
     */
    public function discoverStdio(
        string $basePath,
        array $directories,
        array $excludeDirs = [],
        array $namePatterns = self::DEFAULT_NAME_PATERNS,
    ): DiscoveryState {
        return $this->filter(
            $this->discover($basePath, $directories, $excludeDirs, $namePatterns),
            fn (ElementReference $reference): bool => ($this->reflection($reference->handler)?->getAttributes(RequiresHttp::class) ?? []) === [],
        );
    }

    /**
     * @param  list<string>  $directories
     * @param  list<string>  $excludeDirs
     * @param  list<string>  $namePatterns
     */
    private function discoverAll(string $basePath, array $directories, array $excludeDirs, array $namePatterns): DiscoveryState
    {
        $discovered = $this->discoverer->discover($basePath, $directories, $excludeDirs, $namePatterns);

        $registered = $this->registry->definitions();

        return new DiscoveryState(
            tools: $this->merge($discovered->getTools(), $registered->getTools()),
            resources: $this->merge($discovered->getResources(), $registered->getResources()),
            prompts: $this->merge($discovered->getPrompts(), $registered->getPrompts()),
            resourceTemplates: $this->merge($discovered->getResourceTemplates(), $registered->getResourceTemplates()),
        );
    }

    /**
     * @template T of ElementReference
     *
     * @param  array<string, T>  $existing
     * @param  array<string, T>  $additional
     * @return array<string, T>
     */
    private function merge(array $existing, array $additional): array
    {
        $duplicates = array_intersect_key($existing, $additional);

        if ($duplicates !== []) {
            throw new LogicException('Duplicate MCP capability identities: '.implode(', ', array_keys($duplicates)));
        }

        return $existing + $additional;
    }

    /** @param Closure(ElementReference): bool $allows */
    private function filter(DiscoveryState $discovered, Closure $allows): DiscoveryState
    {
        return new DiscoveryState(
            tools: array_filter($discovered->getTools(), $allows),
            resources: array_filter($discovered->getResources(), $allows),
            prompts: array_filter($discovered->getPrompts(), $allows),
            resourceTemplates: array_filter($discovered->getResourceTemplates(), $allows),
        );
    }

    private function isPublic(ElementReference $reference): bool
    {
        return ($this->reflection($reference->handler)?->getAttributes(PublicMcp::class) ?? []) !== [];
    }

    private function allows(ElementReference $reference): bool
    {
        $method = $this->reflection($reference->handler);
        $requiresAdminChanges = ($method?->getAttributes(RequiresAdminChanges::class) ?? []) !== [];
        $requiresAdmin = $requiresAdminChanges || ($method?->getAttributes(RequiresAdmin::class) ?? []) !== [];
        $requiredPermissions = $method?->getAttributes(RequiresPermission::class) ?? [];

        if (! $requiresAdmin && $requiredPermissions === []) {
            return true;
        }

        $user = $this->request->craftUser();

        if (! $user) {
            return false;
        }

        if ($requiresAdmin && ! $user->isAdmin()) {
            return false;
        }

        if ($requiresAdminChanges && ! $this->config->allowAdminChanges) {
            return false;
        }

        return array_all($requiredPermissions, fn ($attribute) => $user->can($attribute->newInstance()->permission));
    }

    /**
     * @param  Closure|array{0: object|string, 1: string}|string  $handler
     */
    private function reflection(Closure|array|string $handler): ?ReflectionMethod
    {
        if ($handler instanceof Closure) {
            $function = new ReflectionFunction($handler);
            $scope = $function->getClosureScopeClass();

            return $scope?->hasMethod($function->getName())
                ? $scope->getMethod($function->getName())
                : null;
        }

        if (is_string($handler)) {
            return null;
        }

        return new ReflectionMethod($handler[0], $handler[1]);
    }
}
