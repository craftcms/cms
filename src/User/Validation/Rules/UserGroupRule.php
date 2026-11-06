<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Validation\Rules;

use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Validation\Rules\ModelReferenceRule;
use Override;

use function CraftCms\Cms\t;

/**
 * Validates that a value references an existing user group by ID, UID or handle.
 *
 * @since 6.0.0
 */
readonly class UserGroupRule extends ModelReferenceRule
{
    #[Override]
    protected function resolve(mixed $value): ?object
    {
        return UserGroups::resolveGroup($value, $this->by);
    }

    #[Override]
    protected function defaultMessage(string $value): string
    {
        return t('No user group found for “{group}”.', ['group' => $value]);
    }
}
