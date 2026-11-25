<?php

declare(strict_types=1);

namespace craft\mcp\contentmodel\checks;

use craft\models\EntryType;
use craft\services\Entries as EntryTypes;
use Illuminate\Support\Collection;

/** @since 5.12.0 */
class EmptyEntryTypes extends BaseCheck
{
    public function __construct(
        private readonly EntryTypes $entryTypes,
    ) {
    }

    public static function id(): string
    {
        return 'empty-entry-types';
    }

    protected function findings(): Collection
    {
        $entryTypeIds = $this->entryIdsBy('typeId');

        return collect($this->entryTypes->getAllEntryTypes())
            ->reject(static fn(EntryType $entryType): bool => isset($entryTypeIds[$entryType->id]))
            ->map($this->modelFinding(...))
            ->values();
    }
}
