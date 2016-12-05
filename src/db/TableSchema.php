<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\db;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class TableSchema extends \yii\db\TableSchema
{
    /**
     * Holds extended foreign key information.
     *
     * @var array
     */
    private $_extendedForeignKeys = [];

    /**
     * Returns the array of extended foreign keys.
     *
     * @return array
     */
    public function getExtendedForeignKeys()
    {
        return $this->_extendedForeignKeys;
    }

    /**
     * Adds an extended foreign key to the internal array.
     *
     * @param $extendedForeignKey
     */
    public function addExtendedForeignKey($extendedForeignKey)
    {
        $this->_extendedForeignKeys[] = $extendedForeignKey;
    }
}
