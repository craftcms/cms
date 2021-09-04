<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\conditions\elements;

use craft\conditions\QueryConditionInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;

/**
 * ElementQueryConditionInterface defines the common interface to be implemented by element query condition classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ElementQueryConditionInterface extends QueryConditionInterface
{
    /**
     * @return ElementQuery
     */
    public function getElementQuery(): ElementQueryInterface;
}
