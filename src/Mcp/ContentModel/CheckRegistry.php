<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Mcp\ContentModel\Checks\DuplicateFieldCandidates;
use CraftCms\Cms\Mcp\ContentModel\Checks\EmptyEntryTypes;
use CraftCms\Cms\Mcp\ContentModel\Checks\EmptySections;
use CraftCms\Cms\Mcp\ContentModel\Checks\MissingAltText;
use CraftCms\Cms\Mcp\ContentModel\Checks\UnusedEntryTypes;
use CraftCms\Cms\Mcp\ContentModel\Checks\UnusedFields;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers content-model audit checks by their IDs.
 *
 * Plugins may register checks from their service provider:
 *
 * ```php
 * public function boot(CheckRegistry $checks): void
 * {
 *     $checks->register(MyCheck::class);
 * }
 * ```
 *
 * @extends TypeRegistry<Check>
 *
 * @since 6.0.0
 */
#[Singleton]
class CheckRegistry extends TypeRegistry
{
    protected const string CONTRACT = Check::class;

    protected const array DEFAULT_TYPES = [
        DuplicateFieldCandidates::class,
        EmptyEntryTypes::class,
        EmptySections::class,
        MissingAltText::class,
        UnusedEntryTypes::class,
        UnusedFields::class,
    ];

    /** @return list<string> */
    public function ids(): array
    {
        return $this->types()
            ->map(static fn (string $type): string => $type::id())
            ->all();
    }

    public function find(string $id): ?Check
    {
        $type = $this->typeByIdentity($id);

        return $type === null ? null : app($type);
    }

    /** @param class-string<Check> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::id();
    }
}
