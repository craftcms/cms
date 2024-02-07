<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\base\NotSupportedException;
use yii\db\ExpressionBuilderInterface;
use yii\db\ExpressionBuilderTrait;
use yii\db\ExpressionInterface;

/**
 * CallbackExpressionBuilder builds a callback expression
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class CallbackExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * @inheritdoc
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        if (!$expression instanceof CallbackExpression) {
            throw new NotSupportedException('$expression must be an instance of CallbackExpression.');
        }

        return call_user_func($expression->callback, $this->queryBuilder, $params);
    }
}
