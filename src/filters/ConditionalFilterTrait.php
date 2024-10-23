<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

/**
 * Filter for ensuring the user should be able to access the configured utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
trait ConditionalFilterTrait
{
    /**
     * @var callable|null A PHP callable that determines when this filter should be applied.
     */
    public mixed $when = null;

    /**
     * @inheritdoc
     */
    protected function isActive(mixed $action): bool
    {
        // Retain only/except logic
        if (!parent::isActive($action)) {
            return false;
        }

        if (isset($this->when) && !call_user_func($this->when, $action)) {
            return false;
        }

        return true;
    }
}
