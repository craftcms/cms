<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

/**
 * Active Query class.
 *
 * @property-read string $alias The table alias for [[modelClass]].
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.13
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    /**
     * Returns the table alias for [[modelClass]].
     *
     * @return string
     */
    public function getAlias(): string
    {
        [, $alias] = $this->getTableNameAndAlias();
        return $alias;
    }
}
