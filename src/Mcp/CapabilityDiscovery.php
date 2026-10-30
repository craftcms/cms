<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Mcp\Attributes\PublicMcp;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\Attributes\RequiresEdition;
use CraftCms\Cms\Mcp\Attributes\RequiresHttp;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\Enums\McpScope;
use Illuminate\Http\Request;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use LogicException;
use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\ElementReference;
use Mcp\Capability\Registry\ToolReference;
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
            fn (ElementReference $reference): bool => ! $this->isPublic($reference)
                && $this->allows($reference)
                && $this->tokenAllows($reference),
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
     * Discovers the capabilities the current user can use without an access token, for the stdio server and the AI
     * assistant. Capabilities that only work over HTTP are left out.
     *
     * @param  list<string>  $directories
     * @param  list<string>  $excludeDirs
     * @param  list<string>  $namePatterns
     */
    public function discoverWithoutToken(
        string $basePath,
        array $directories,
        array $excludeDirs = [],
        array $namePatterns = self::DEFAULT_NAME_PATERNS,
    ): DiscoveryState {
        $discovered = $this->discoverAll($basePath, $directories, $excludeDirs, $namePatterns);

        return $this->filter(
            $discovered,
            fn (ElementReference $reference): bool => ! $this->isPublic($reference)
                && $this->allows($reference)
                && ($this->reflection($reference->handler)?->getAttributes(RequiresHttp::class) ?? []) === [],
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

        if (! $this->editionAllows($method)) {
            return false;
        }

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

    /** Whether the request's access token has the scope the capability requires. */
    private function tokenAllows(ElementReference $reference): bool
    {
        $user = $this->request->user('craft-mcp');
        $token = is_object($user) && method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        if (! $token instanceof ScopeAuthorizable) {
            return false;
        }

        return $this->requiredScope($reference)->isGrantedTo($token);
    }

    private function requiredScope(ElementReference $reference): McpScope
    {
        $method = $this->reflection($reference->handler);

        if (($method?->getAttributes(RequiresAdmin::class) ?? []) !== [] || ($method?->getAttributes(RequiresAdminChanges::class) ?? []) !== []) {
            return McpScope::Admin;
        }

        if ($reference instanceof ToolReference && $reference->tool->annotations?->readOnlyHint !== true) {
            return McpScope::Write;
        }

        return McpScope::Read;
    }

    /** Whether the edition meets any `RequiresEdition` on the tool or its capability class. */
    private function editionAllows(?ReflectionMethod $method): bool
    {
        if ($method === null) {
            return true;
        }

        $attributes = [
            ...$method->getDeclaringClass()->getAttributes(RequiresEdition::class),
            ...$method->getAttributes(RequiresEdition::class),
        ];

        return array_all($attributes, fn ($attribute) => Edition::isAtLeast($attribute->newInstance()->edition));
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
