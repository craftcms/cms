<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use creocoder\nestedsets\NestedSetsQueryBehavior;

/**
 * @inheritdoc
 * @mixin NestedSetsQueryBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class StructuredElementQuery extends ActiveQuery
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            NestedSetsQueryBehavior::class,
        ];
    }
}
