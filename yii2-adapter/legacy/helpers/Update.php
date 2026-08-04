<?php

namespace craft\helpers;

use CraftCms\Cms\Support\PHP;

/**
 * @since 3.5.15
 * @deprecated 6.0.0
 */
class Update
{
    /**
     * Compares the given PHP version constraint with the environment, and returns any issues with it.
     *
     * @param string $constraint The PHP version constraint
     * @param string|null $error The error message
     * @param bool $withLink Whether the error message should include a “Learn more” link
     * @return bool Whether the environment passes the PHP constraint
     *
     * @deprecated 6.0.0 use {@see PHP::checkConstraint()} instead.
     */
    public static function checkPhpConstraint(string $constraint, ?string &$error = null, bool $withLink = false): bool
    {
        $error = PHP::checkConstraint($constraint, $withLink);

        if ($error) {
            return false;
        }

        return true;
    }
}
