<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Validation\Rules;

use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Validation\Rules\ModelReferenceRule;
use Override;

use function CraftCms\Cms\t;

/**
 * Validates that a value references an existing section by ID, UID or handle.
 *
 * @since 6.0.0
 */
readonly class SectionRule extends ModelReferenceRule
{
    #[Override]
    protected function resolve(mixed $value): ?object
    {
        return Sections::resolveSection($value, $this->by);
    }

    #[Override]
    protected function defaultMessage(string $value): string
    {
        return t('No section found for “{section}”.', ['section' => $value]);
    }
}
