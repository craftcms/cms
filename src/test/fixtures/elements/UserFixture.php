<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use craft\elements\User;

/**
 * Class UserFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
abstract class UserFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    public $modelClass = User::class;

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['username', 'email']);
    }
}
