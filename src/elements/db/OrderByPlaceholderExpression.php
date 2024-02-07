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
 * @internal
 */
class OrderByPlaceholderExpression extends Expression
{
    public function __construct($params = [], $config = [])
    {
        parent::__construct('', $params, $config);
    }
}
