<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Craft;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;
use yii\base\InvalidArgumentException;
use yii\db\ActiveRecord;
use yii\db\TableSchema;
use yii\log\Logger;
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

        $fieldsService = Craft::$app->getFields();

        foreach ($this->getData() as $key => $row) {
            $modelClass = $this->modelClass;

            // Fixture data may pass in props that are not for the db. We thus run an extra check to ensure
            // that we are deleting only based on columns that *actually* exist in the schema
            $correctRow = $row;

            // Set the field layout if it exists.
            $fieldLayout = null;
            if (isset($row['fieldLayoutType'])) {
                $fieldLayoutType = ArrayHelper::remove($row, 'fieldLayoutType');
                $fieldLayout = $fieldsService->getLayoutByType($fieldLayoutType);
                if ($fieldLayout->id === null) {
                    if (function_exists('codecept_debug')) {
                        codecept_debug("Field layout with type: $fieldLayoutType could not be found");
                    } else {
                        Craft::getLogger()->log(
                            "Field layout with type: $fieldLayoutType could not be found",
                            Logger::LEVEL_WARNING,
                            'testing'
                        );
                    }
                }
            } elseif (isset($row['fieldLayoutUid'])) {
                $fieldLayoutUid = ArrayHelper::remove($row, 'fieldLayoutUid');
                $fieldLayout = $fieldsService->getLayoutByUid($fieldLayoutUid);
                if (!$fieldLayout) {
                    $fieldLayout = new FieldLayout([
                        'type' => Entry::class,
                        'uid' => $fieldLayoutUid,
                    ]);
                    $fieldsService->saveLayout($fieldLayout);
                }
            }
            if ($fieldLayout?->id !== null) {
                $row['fieldLayoutId'] = $fieldLayout->id;
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
