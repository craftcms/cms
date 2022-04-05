<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\FieldInterface;
use craft\db\Table;
use craft\fields\Matrix;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\i18n\Translation;
use craft\queue\BaseJob;
use yii\base\Exception;

/**
 * FindAndReplace job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FindAndReplace extends BaseJob
{
    /**
     * @var string|null The search text
     */
    public ?string $find = null;

    /**
     * @var string|null The replacement text
     */
    public ?string $replace = null;

    /**
     * @var array
     */
    private array $_textColumns;

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function execute($queue): void
    {
        // Find all the textual field columns
        $this->_textColumns = [
            [Table::CONTENT, 'title'],
        ];

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($field instanceof Matrix) {
                $this->_checkMatrixField($field);
            } else {
                $this->_checkField($field, Table::CONTENT, 'field_');
            }
        }

        // Now loop through them and perform the find/replace
        $totalTextColumns = count($this->_textColumns);
        foreach ($this->_textColumns as $i => [$table, $column]) {
            $this->setProgress($queue, $i / $totalTextColumns);

            Db::replace($table, $column, $this->find, $this->replace);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Replacing “{find}” with “{replace}”', [
            'find' => $this->find,
            'replace' => $this->replace,
        ]);
    }

    /**
     * Checks whether the given field is saving data into a textual column, and saves it accordingly.
     *
     * @param FieldInterface $field
     * @param string $table
     * @param string $fieldColumnPrefix
     */
    private function _checkField(FieldInterface $field, string $table, string $fieldColumnPrefix): void
    {
        if (!$field::hasContentColumn()) {
            return;
        }

        $columnType = $field->getContentColumnType();

        if (is_array($columnType)) {
            foreach (array_keys($columnType) as $i => $key) {
                if (Db::isTextualColumnType($columnType[$key])) {
                    $column = ElementHelper::fieldColumn($fieldColumnPrefix, $field->handle, $field->columnSuffix, $i !== 0 ? $key : null);
                    $this->_textColumns[] = [$table, $column];
                }
            }
        } elseif (Db::isTextualColumnType($columnType)) {
            $column = ElementHelper::fieldColumn($fieldColumnPrefix, $field->handle, $field->columnSuffix);
            $this->_textColumns[] = [$table, $column];
        }
    }

    /**
     * Registers any textual columns associated with the given Matrix field.
     *
     * @param Matrix $matrixField
     * @throws Exception if the content table can't be determined
     */
    private function _checkMatrixField(Matrix $matrixField): void
    {
        $blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($matrixField->id);

        foreach ($blockTypes as $blockType) {
            $fieldColumnPrefix = 'field_' . $blockType->handle . '_';

            foreach ($blockType->getCustomFields() as $field) {
                $this->_checkField($field, $matrixField->contentTable, $fieldColumnPrefix);
            }
        }
    }
}
