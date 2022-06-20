<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Craft;
use yii\base\InvalidArgumentException;
use yii\db\ActiveRecord;
use yii\db\TableSchema;
use yii\test\ActiveFixture as YiiActiveFixture;
use yii\test\BaseActiveFixture;

/**
 * Class Fixture.
 *
 * @property ActiveRecord[] $data
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.6.0
 */
class ActiveFixture extends YiiActiveFixture
{
    /**
     * @var array
     */
    protected array $ids = [];

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        $tableSchema = $this->getTableSchema();
        $this->data = [];
        foreach ($this->getData() as $key => $row) {
            $modelClass = $this->modelClass;

            // Fixture data may pass in props that are not for the db. We thus run an extra check to ensure
            // that we are deleting only based on columns that *actually* exist in the schema
            $correctRow = $row;

            // Set the field layout if it exists.
            if (isset($row['fieldLayoutType'])) {
                $fieldLayoutType = $row['fieldLayoutType'];
                unset($row['fieldLayoutType']);

                $fieldLayout = Craft::$app->getFields()->getLayoutByType($fieldLayoutType);
                if ($fieldLayout->id) {
                    $row['fieldLayoutId'] = $fieldLayout->id;
                } else {
                    codecept_debug("Field layout with type: $fieldLayoutType could not be found");
                }
            }

            foreach ($row as $columnName => $rowValue) {
                $correctRow = $this->ensureColumnIntegrity($tableSchema, $row, $columnName);
            }

            $arInstance = new $modelClass($correctRow);
            if (!$arInstance->save()) {
                throw new InvalidArgumentException('Unable to save fixture data');
            }

            $this->data[$key] = $arInstance;
            $this->ids[$key] = $arInstance->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function unload(): void
    {
        foreach ($this->data as $arInstance) {
            $arInstance->delete();
        }

        $this->ids = [];
        BaseActiveFixture::unload();
    }

    /**
     * @param TableSchema $table
     * @param array $row
     * @param string $column
     * @return array $row
     */
    public function ensureColumnIntegrity(TableSchema $table, array $row, string $column): array
    {
        if (!isset($table->columns[$column])) {
            unset($row[$column]);
        }

        return $row;
    }
}
