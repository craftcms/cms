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
use yii\db\ExpressionInterface as BaseExpressionInterface;

/**
 * ExpressionBuilder builds [[ExpressionInterface]] DB expressions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.1.0
 */
class ExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    public function build(BaseExpressionInterface $expression, array &$params = [])
    {
        if (!$expression instanceof ExpressionInterface) {
            throw new NotSupportedException(sprintf('$expression must be an instance of %s.', ExpressionInterface::class));
        }

        return $expression->getSql($params);
    }
}
