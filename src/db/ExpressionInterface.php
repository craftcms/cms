<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\db\ExpressionInterface as BaseExpressionInterface;

/**
 * ExpressionInterface defines the common interface that should be implemented by expressions
 * which should be built by [[ExpressionBuilder]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.1.0
 */
interface ExpressionInterface extends BaseExpressionInterface
{
    public function getSql(array &$params): string;
}
