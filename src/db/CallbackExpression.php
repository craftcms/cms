<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\base\BaseObject;
use yii\db\ExpressionInterface;

/**
 * CallbackExpression represents a DB expression where the SQL won’t get generated until execution time.
 *
 * Usage:
 *
 * ```php
 * $query->andWhere(
 *     new CallbackExpression(function(array &$params): string {
 *         $params['foo'] = 'bar';
 *         return '[[column_name]] = :foo';
 *     })
 * );
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class CallbackExpression extends BaseObject implements ExpressionInterface
{
    /**
     * Constructor
     *
     * @param callable $callback the DB expression callback
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(
        public $callback,
        array $config = [],
    ) {
        parent::__construct($config);
    }
}
