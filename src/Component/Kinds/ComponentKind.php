<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Kinds;

use CraftCms\Cms\Component\TypeRegistry;

/**
 * A kind of extendable component that can be chosen and configured, such as field types.
 *
 * Kinds list their available types and describe each type’s settings as JSON Schema, for clients such as the
 * MCP server’s `component-types.*` tools. Register kinds with {@see ComponentKinds}:
 *
 * ```php
 * public function boot(ComponentKinds $componentKinds): void
 * {
 *     $componentKinds->register(MyKind::class);
 * }
 * ```
 *
 * Kinds whose types live in a {@see TypeRegistry} can extend {@see TypeRegistryKind}.
 *
 * @since 6.0.0
 */
interface ComponentKind
{
    /** The kind’s handle, such as `field`. */
    public static function handle(): string;

    /** What the kind’s types are, such as “Field types”. */
    public function description(): string;

    /** Instructions for using a type and its settings, written for agents and other clients. */
    public function usage(): string;

    /** What the `scope` argument selects for this kind, or `null` if the kind doesn’t take one. */
    public function scope(): ?string;

    /**
     * Returns the available types. Each type’s `config`, when given, is the config to start a component from.
     *
     * @param  string|null  $scope  Provided when {@see scope()} isn’t `null`.
     * @return list<array{type: string, name: string, group?: string, config?: array<string, mixed>}>
     */
    public function types(?string $scope): array;

    /**
     * Describes one of the types returned by {@see types()}, normally with a `settingsSchema`.
     *
     * @param  string|null  $scope  Provided when {@see scope()} isn’t `null`.
     * @return array<string, mixed>
     */
    public function describe(string $type, ?string $scope): array;
}
