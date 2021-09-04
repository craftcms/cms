<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\conditions;

use yii\db\QueryInterface;

/**
 * QueryConditionInterface defines the common interface to be implemented by query condition classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface QueryConditionInterface extends ConditionInterface
{
    /**
     * Returns a query that has been modified by the rules.
     *
     * @return QueryInterface
     */
    public function getQuery(): QueryInterface;

    /**
     * Modifies a given query based on the configured condition rules.
     *
     * @param QueryInterface $query
     * @return QueryInterface
     */
    public function modifyQuery(QueryInterface $query): QueryInterface;
}
