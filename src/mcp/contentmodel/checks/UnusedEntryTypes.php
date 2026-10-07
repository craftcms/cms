<?php

declare(strict_types=1);

namespace craft\mcp\contentmodel\checks;

use craft\models\EntryType;
use craft\services\Entries as EntryTypes;
use Illuminate\Support\Collection;

/** @since 5.12.0 */
class UnusedEntryTypes extends BaseCheck
{
    public function __construct(
        private readonly EntryTypes $entryTypes,
    ) {
    }

    public static function id(): string
    {
        return 'unused-entry-types';
    }

    protected function findings(): Collection
    {
        return collect($this->entryTypes->getAllEntryTypes())
            ->filter(static fn(EntryType $entryType): bool => $entryType->findUsages() === [])
            ->map($this->modelFinding(...))
            ->values();
    }
}
