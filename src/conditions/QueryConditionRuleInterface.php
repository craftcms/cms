<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\conditions;

use yii\db\QueryInterface;

/**
 * QueryConditionRuleInterface defines the common interface to be implemented by query condition rule classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface QueryConditionRuleInterface extends ConditionRuleInterface
{
    /**
     * Modifies the given query based.
     *
     * @param QueryInterface $query
     * @return QueryInterface
     */
    public function modifyQuery(QueryInterface $query): QueryInterface;
}
