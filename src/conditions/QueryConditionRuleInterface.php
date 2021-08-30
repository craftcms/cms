<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\conditions;

use yii\db\QueryInterface;

/**
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface QueryConditionRuleInterface
{
    /**
     * Takes an element query and modifies it.
     *
     * @param QueryInterface $query
     * @return QueryInterface
     * @since 4.0
     */
    public function modifyQuery(QueryInterface $query): QueryInterface;
}