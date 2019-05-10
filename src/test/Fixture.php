<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craft\test;


use yii\db\Exception;
use yii\db\TableSchema;
use yii\test\ActiveFixture;

/**
 * Class Fixture.
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class Fixture extends ActiveFixture
{

    public function unload() {
        $table = $this->getTableSchema();
        foreach ($this->getData() as $toBeDeletedRow) {

            // Fixture data may pass in props that are not for the db. We thus run an extra check to ensure
            // that we are deleting only based on columns that *actually* exist in the schema
            $correctRow = $toBeDeletedRow;
            foreach ($toBeDeletedRow as $columnName => $rowValue) {
                $correctRow = $this->ensureColumnIntegrity($table, $toBeDeletedRow, $columnName);
            }

            $this->deleteRow($table->fullName, $correctRow);
        }
    }

    /**
     * @param TableSchema $table
     * @param array $row
     * @param string $column
     * @return array $row
     */
    public function ensureColumnIntegrity(TableSchema $table, array $row, string $column) : array
    {
        if (!isset($table->columns[$column])) {
            unset($row[$column]);
        }

        return $row;
    }

    /**
     * @param $tableName
     * @param $toBeDeletedRow
     * @return int
     * @throws Exception
     */
    public function deleteRow($tableName, $toBeDeletedRow): int
    {
        return $this->db->createCommand()->delete($tableName, $toBeDeletedRow)->execute();
    }
}