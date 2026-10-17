<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Validation\Rules;

use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Validation\Rules\ModelReferenceRule;
use Override;

use function CraftCms\Cms\t;

/**
 * Validates that a value references an existing entry type by ID, UID or handle.
 *
 * @since 6.0.0
 */
readonly class EntryTypeRule extends ModelReferenceRule
{
    #[Override]
    protected function resolve(mixed $value): ?object
    {
        return EntryTypes::resolveEntryType($value, $this->by);
    }

    #[Override]
    protected function defaultMessage(string $value): string
    {
        return t('No entry type found for “{entryType}”.', ['entryType' => $value]);
    }
}
