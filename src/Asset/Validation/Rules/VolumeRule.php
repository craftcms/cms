<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Validation\Rules;

use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Validation\Rules\ModelReferenceRule;
use Override;

use function CraftCms\Cms\t;

/**
 * Validates that a value references an existing volume by ID, UID or handle.
 *
 * @since 6.0.0
 */
readonly class VolumeRule extends ModelReferenceRule
{
    #[Override]
    protected function resolve(mixed $value): ?object
    {
        return Volumes::resolveVolume($value, $this->by);
    }

    #[Override]
    protected function defaultMessage(string $value): string
    {
        return t('No volume found for “{volume}”.', ['volume' => $value]);
    }
}
