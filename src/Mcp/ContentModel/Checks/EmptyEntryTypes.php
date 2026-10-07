<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel\Checks;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use Illuminate\Support\Collection;

/** @since 6.0.0 */
class EmptyEntryTypes extends BaseCheck
{
    public function __construct(
        private readonly EntryTypes $entryTypes,
    ) {}

    public static function id(): string
    {
        return 'empty-entry-types';
    }

    protected function findings(): Collection
    {
        $entryTypeIds = $this->entryIdsBy('typeId');

        return $this->entryTypes
            ->getAllEntryTypes()
            ->reject(static fn (EntryType $entryType): bool => isset($entryTypeIds[$entryType->id]))
            ->map($this->modelFinding(...))
            ->values();
    }
}
