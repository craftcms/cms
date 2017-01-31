<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db;

use yii\db\Expression;

/**
 * FixedOrderExpression represents the SQL used to apply a fixed order to a DB result.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FixedOrderExpression extends Expression
{
    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param string     $column The column name that contains the values.
     * @param array      $values The column values, in the order in which the rows should be returned in.
     * @param Connection $db     The DB connection
     * @param array      $params Parameters
     * @param array      $config Name-value pairs that will be used to initialize the object properties.
     */
    public function __construct(string $column, array $values, Connection $db, array $params = [], array $config = [])
    {
        $expression = $db->getQueryBuilder()->fixedOrder($column, $values);
        parent::__construct($expression, $params, $config);
    }
}
