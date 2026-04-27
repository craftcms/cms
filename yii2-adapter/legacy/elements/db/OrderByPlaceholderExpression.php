<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use yii\db\Expression;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression} instead.
 * @internal
 */
class OrderByPlaceholderExpression extends Expression
{
    public function __construct($params = [], $config = [])
    {
        parent::__construct('', $params, $config);
    }
}
