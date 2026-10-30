<?php

declare(strict_types=1);

namespace craft\mcp;

use Closure;
use Craft;
use craft\config\GeneralConfig;
use craft\elements\User;
use craft\mcp\attributes\PublicMcp;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\mcp\attributes\RequiresHttp;
use craft\mcp\attributes\RequiresPermission;
use craft\mcp\events\DiscoveringCapability;
use craft\services\Mcp as McpService;
use LogicException;
use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Registry\ElementReference;
use Mcp\Capability\Registry\ToolReference;
use ReflectionFunction;
use ReflectionMethod;

/**
 * @since 5.12.0
 */
readonly class CapabilityDiscovery implements DiscovererInterface
{
    private DiscovererInterface $discoverer;

    public function __construct(
        private GeneralConfig $config,
        private CapabilityRegistry $registry,
        ?DiscovererInterface $discoverer = null,
    ) {
        $this->discoverer = $discoverer ?? new Discoverer();
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
            fn(ElementReference $reference): bool => !$this->isPublic($reference)
                && $this->allows($reference)
                && $this->tokenAllows($reference)
                && $this->pluginsAllow($reference),
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
            fn(ElementReference $reference): bool => $this->isPublic($reference) && $this->allows($reference),
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
        $discovered = $this->discoverAll($basePath, $directories, $excludeDirs, $namePatterns);

        return $this->filter(
            $discovered,
            fn(ElementReference $reference): bool => !$this->isPublic($reference)
                && $this->allows($reference)
                && ($this->reflection($reference->handler)?->getAttributes(RequiresHttp::class) ?? []) === []
                && $this->pluginsAllow($reference),
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
            throw new LogicException('Duplicate MCP capability identities: ' . implode(', ', array_keys($duplicates)));
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

        if (!$requiresAdmin && $requiredPermissions === []) {
            return true;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user instanceof User) {
            return false;
        }

        if ($requiresAdmin && !$user->admin) {
            return false;
        }

        if ($requiresAdminChanges && !$this->config->allowAdminChanges) {
            return false;
        }

        foreach ($requiredPermissions as $attribute) {
            if (!$user->can($attribute->newInstance()->permission)) {
                return false;
            }
        }

        return true;
    }

    private function pluginsAllow(ElementReference $reference): bool
    {
        $mcp = Craft::$app->get('mcp');

        if (!$mcp->hasEventHandlers(McpService::EVENT_DISCOVERING_CAPABILITY)) {
            return true;
        }

        $user = Craft::$app->getUser()->getIdentity();
        $event = new DiscoveringCapability([
            'reference' => $reference,
            'user' => $user instanceof User ? $user : null,
        ]);
        $mcp->trigger(McpService::EVENT_DISCOVERING_CAPABILITY, $event);

        return $event->allowed;
    }

    /** Whether the access token that authenticated the request grants the scope the capability requires. */
    private function tokenAllows(ElementReference $reference): bool
    {
        $scopes = Craft::$app->get('mcp')->tokenScopes;

        if ($scopes === null) {
            return false;
        }

        $resource = Craft::$app->get('oauth')->getResource('mcp');

        return array_intersect($resource->satisfyingLevels($this->requiredScope($reference)), $scopes) !== [];
    }

    private function requiredScope(ElementReference $reference): string
    {
        $method = $this->reflection($reference->handler);

        if (($method?->getAttributes(RequiresAdmin::class) ?? []) !== [] || ($method?->getAttributes(RequiresAdminChanges::class) ?? []) !== []) {
            return McpService::SCOPE_ADMIN;
        }

        if ($reference instanceof ToolReference && $reference->tool->annotations?->readOnlyHint !== true) {
            return McpService::SCOPE_WRITE;
        }

        return McpService::SCOPE_READ;
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
