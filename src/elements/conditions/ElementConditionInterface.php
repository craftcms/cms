<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\conditions;

use craft\base\conditions\ConditionInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * ElementConditionInterface defines the common interface to be implemented by element conditions.
 *
 * A base implementation is provided by [[ElementCondition]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ElementConditionInterface extends ConditionInterface
{
    /**
     * Modifies a given query based on the configured condition rules.
     *
     * @param ElementQueryInterface $query
     */
    public function modifyQuery(ElementQueryInterface $query): void;
}
