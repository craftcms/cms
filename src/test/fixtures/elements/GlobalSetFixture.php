<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;


use craft\elements\GlobalSet;

/**
 * Class GlobalSetFixture
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @todo https://github.com/robuust/craft-fixtures/blob/master/src/base/GlobalSetFixture.php#L50 Why?
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
abstract class GlobalSetFixture extends ElementFixture
{
    // Public properties
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public $modelClass = GlobalSet::class;

    // Protected Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || $key === 'handle';
    }
}
