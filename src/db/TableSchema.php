<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
    public function getExtendedForeignKeys(): array
    {
        return $this->_extendedForeignKeys;
    }

    /**
     * Adds an extended foreign key to the internal array.
     *
     * @param int $key
     * @param array $extendedForeignKey
     */
    public function addExtendedForeignKey($key, $extendedForeignKey)
    {
        $this->_extendedForeignKeys[$key] = $extendedForeignKey;
    }
}
