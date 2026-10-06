<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel\Checks;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use Illuminate\Support\Collection;

/** @since 6.0.0 */
class UnusedEntryTypes extends BaseCheck
{
    public function __construct(
        private readonly EntryTypes $entryTypes,
    ) {}

    public static function id(): string
    {
        return 'unused-entry-types';
    }

    protected function findings(): Collection
    {
        return $this->entryTypes
            ->getAllEntryTypes()
            ->filter(static fn (EntryType $entryType): bool => $entryType->findUsages() === [])
            ->map($this->modelFinding(...))
            ->values();
    }
}
