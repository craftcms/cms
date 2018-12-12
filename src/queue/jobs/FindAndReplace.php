<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\fields\Matrix;
use craft\queue\BaseJob;
use yii\base\Exception;

/**
 * FindAndReplace job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FindAndReplace extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The search text
     */
    public $find;

    /**
     * @var string|null The replacement text
     */
    public $replace;

    /**
     * @var
     */
    private $_textColumns;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function execute($queue)
    {
        // Find all the textual field columns
        $this->_textColumns = [
            ['{{%content}}', 'title'],
        ];

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($field instanceof Matrix) {
                $this->_checkMatrixField($field);
            } else {
                $this->_checkField($field, '{{%content}}', 'field_');
            }
        }

        // Now loop through them and perform the find/replace
        $totalTextColumns = count($this->_textColumns);
        foreach ($this->_textColumns as $i => list($table, $column)) {
            $this->setProgress($queue, $i / $totalTextColumns);

            Craft::$app->getDb()->createCommand()
                ->replace($table, $column, $this->find, $this->replace)
                ->execute();
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Replacing “{find}” with “{replace}”', [
            'find' => $this->find,
            'replace' => $this->replace
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Checks whether the given field is saving data into a textual column, and saves it accordingly.
     *
     * @param FieldInterface $field
     * @param string $table
     * @param string $fieldColumnPrefix
     */
    private function _checkField(FieldInterface $field, string $table, string $fieldColumnPrefix)
    {
        /** @var Field $field */
        if (!$field::hasContentColumn()) {
            return;
        }

        $columnType = $field->getContentColumnType();

        if (!preg_match('/^\w+/', $columnType, $matches)) {
            return;
        }

        $columnType = strtolower($matches[0]);

        if (in_array($columnType, [
            'tinytext',
            'mediumtext',
            'longtext',
            'text',
            'varchar',
            'string',
            'char'
        ], true)) {
            $this->_textColumns[] = [$table, $fieldColumnPrefix . $field->handle];
        }
    }

    /**
     * Registers any textual columns associated with the given Matrix field.
     *
     * @param Matrix $matrixField
     * @throws Exception if the content table can't be determined
     */
    private function _checkMatrixField(Matrix $matrixField)
    {
        $blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($matrixField->id);

        foreach ($blockTypes as $blockType) {
            $fieldColumnPrefix = 'field_' . $blockType->handle . '_';

            foreach ($blockType->getFields() as $field) {
                $this->_checkField($field, $matrixField->contentTable, $fieldColumnPrefix);
            }
        }
    }
}
