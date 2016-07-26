<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\db\Query;
use craft\app\events\ElementEvent;
use craft\app\models\FieldLayout;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class Content service.
 *
 * An instance of the Content service is globally accessible in Craft via [[Application::content `Craft::$app->getContent()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Content extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event ElementEvent The event that is triggered after an element's content is saved.
     */
    const EVENT_AFTER_SAVE_CONTENT = 'afterSaveContent';

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $contentTable = '{{%content}}';

    /**
     * @var string
     */
    public $fieldColumnPrefix = 'field_';

    /**
     * @var string
     */
    public $fieldContext = 'global';

    // Public Methods
    // =========================================================================

    /**
     * Returns the content row for a given element, with field column prefixes removed from the keys.
     *
     * @param ElementInterface $element The element whose content we're looking for.
     *
     * @return array|null The element's content row values, or null if the row could not be found
     */
    public function getContentRow(ElementInterface $element)
    {
        /** @var Element $element */
        if (!$element->id || !$element->locale) {
            return null;
        }

        $originalContentTable = $this->contentTable;
        $originalFieldColumnPrefix = $this->fieldColumnPrefix;
        $originalFieldContext = $this->fieldContext;

        $this->contentTable = $element->getContentTable();
        $this->fieldColumnPrefix = $element->getFieldColumnPrefix();
        $this->fieldContext = $element->getFieldContext();

        $row = (new Query())
            ->from($this->contentTable)
            ->where([
                'elementId' => $element->id,
                'locale' => $element->locale
            ])
            ->one();

        if ($row) {
            $row = $this->_removeColumnPrefixesFromRow($row);
        }

        $this->contentTable = $originalContentTable;
        $this->fieldColumnPrefix = $originalFieldColumnPrefix;
        $this->fieldContext = $originalFieldContext;

        return $row;
    }

    /**
     * Populates a given element with its custom field values.
     *
     * @param ElementInterface $element The element for which we should create a new content model.
     *
     * @return void
     */
    public function populateElementContent(ElementInterface $element)
    {
        /** @var Element $element */
        // Make sure the element has content
        if (!$element->hasContent()) {
            return;
        }

        $fieldLayout = $element->getFieldLayout();

        if ($row = $this->getContentRow($element)) {
            $element->contentId = $row['id'];
            if ($element->hasTitles() && isset($row['title'])) {
                $element->title = $row['title'];
            }
            if ($fieldLayout) {
                foreach ($fieldLayout->getFields() as $field) {
                    /** @var Field $field */
                    if ($field::hasContentColumn()) {
                        $element->setFieldValue($field->handle, $row[$field->handle]);
                    }
                }
            }
        }
    }

    /**
     * Saves an element's content.
     *
     * @param ElementInterface $element                    The element whose content we're saving.
     * @param boolean          $validate                   Whether the element's content should be validated first.
     * @param boolean          $updateOtherLocales         Whether any non-translatable fields' values should be copied to the
     *                                                     element's other locales.
     *
     * @return boolean Whether the content was saved successfully. If it wasn't, any validation errors will be saved on the
     *              element and its content model.
     * @throws Exception if $element has not been saved yet
     */
    public function saveContent(ElementInterface $element, $validate = true, $updateOtherLocales = true)
    {
        /** @var Element $element */
        if (!$element->id) {
            throw new Exception('Cannot save the content of an unsaved element.');
        }

        $originalContentTable = $this->contentTable;
        $originalFieldColumnPrefix = $this->fieldColumnPrefix;
        $originalFieldContext = $this->fieldContext;

        $this->contentTable = $element->getContentTable();
        $this->fieldColumnPrefix = $element->getFieldColumnPrefix();
        $this->fieldContext = $element->getFieldContext();

        if (!$validate || $this->validateContent($element)) {
            // Prepare the data to be saved
            $values = [
                'elementId' => $element->id,
                'locale' => $element->locale
            ];
            if ($element->hasTitles() && $element->title) {
                $values['title'] = $element->title;
            }
            $fieldLayout = $element->getFieldLayout();
            if ($fieldLayout) {
                foreach ($fieldLayout->getFields() as $field) {
                    /** @var Field $field */
                    if ($field::hasContentColumn()) {
                        $column = $this->fieldColumnPrefix.$field->handle;
                        $values[$column] = $field->prepareValueForDb($element->getFieldValue($field->handle), $element);
                    }
                }
            }

            // Insert/update the DB row
            if ($element->contentId) {
                // Update the existing row
                Craft::$app->getDb()->createCommand()
                    ->update($this->contentTable, $values, ['id' => $element->contentId])
                    ->execute();
            } else {
                // Insert a new row and store its ID on the element
                Craft::$app->getDb()->createCommand()
                    ->insert($this->contentTable, $values)
                    ->execute();
                $element->contentId = Craft::$app->getDb()->getLastInsertID();
            }

            // Fire an 'afterSaveContent' event
            $this->trigger(static::EVENT_AFTER_SAVE_CONTENT, new ElementEvent([
                'element' => $element
            ]));

            if ($fieldLayout) {
                if ($updateOtherLocales && Craft::$app->isLocalized()) {
                    $this->_duplicateNonTranslatableFieldValues($element, $values, $nonTranslatableFields, $otherContentModels);
                }

                $this->_updateSearchIndexes($element, $fieldLayout, $nonTranslatableFields, $otherContentModels);
            }

            $success = true;
        } else {
            $success = false;
        }

        $this->contentTable = $originalContentTable;
        $this->fieldColumnPrefix = $originalFieldColumnPrefix;
        $this->fieldContext = $originalFieldContext;

        return $success;
    }

    /**
     * Validates some content with a given field layout.
     *
     * @param ElementInterface $element The element whose content should be validated.
     *
     * @return boolean Whether the element's content validates.
     */
    public function validateContent(ElementInterface $element)
    {
        /** @var Element $element */
        $validates = true;
        $fieldLayout = $element->getFieldLayout();

        if ($fieldLayout) {
            foreach ($fieldLayout->getFields() as $field) {
                /** @var Field $field */
                $value = $element->getFieldValue($field->handle);
                $errors = $field->validateValue($value, $element);

                if (!empty($errors) && $errors !== true) {
                    if (!is_array($errors)) {
                        $errors = [$errors];
                    }

                    // Parse any {attribute} and {value} tokens in the error message(s)
                    $i18n = Craft::$app->getI18n();
                    $params = [
                        'attribute' => Craft::t('site', $field->name),
                        'value' => is_array($value) ? 'array()' : $value
                    ];

                    foreach ($errors as $error) {
                        $error = $i18n->format($error, $params, Craft::$app->language);
                        $element->addError('field__'.$field->handle, $error);
                    }

                    $validates = false;
                }
            }
        }

        return $validates;
    }

    // Private Methods
    // =========================================================================

    /**
     * Copies the new values of any non-translatable fields across the element's
     * other locales.
     *
     * @param ElementInterface $element
     * @param array            $sourceValues
     * @param FieldInterface[] &$nonTranslatableFields
     * @param array            &$otherContentRows
     *
     * @return void
     */
    private function _duplicateNonTranslatableFieldValues($element, $sourceValues, &$nonTranslatableFields, &$otherContentRows)
    {
        /** @var Element $element */
        // Get all of the non-translatable fields
        /** @var Field[] $nonTranslatableFields */
        $nonTranslatableFields = [];
        $fieldLayout = $element->getFieldLayout();
        foreach ($fieldLayout->getFields() as $field) {
            /** @var Field $field */
            if (!$field->translatable && $field::hasContentColumn()) {
                $nonTranslatableFields[] = $field;
            }
        }

        if ($nonTranslatableFields) {
            // Get the other locales' content rows
            $otherContentRows = (new Query())
                ->from($this->contentTable)
                ->where(
                    ['and', 'elementId = :elementId', 'locale != :locale'],
                    [
                        ':elementId' => $element->id,
                        ':locale' => $element->locale
                    ])
                ->all();

            // Copy the non-translatable fields' values over to them
            foreach ($otherContentRows as $values) {
                foreach ($nonTranslatableFields as $field) {
                    $column = $this->fieldColumnPrefix.$field->handle;
                    $values[$column] = $sourceValues[$column];
                }

                Craft::$app->getDb()->createCommand()
                    ->update($this->contentTable, $values, ['id' => $values['id']])
                    ->execute();
            }
        }
    }

    /**
     * Updates the search indexes based on the new content values.
     *
     * @param ElementInterface $element
     * @param FieldLayout      $fieldLayout
     * @param array|null       &$nonTranslatableFields
     * @param array|null       &$otherContentModels
     *
     * @return void
     */
    private function _updateSearchIndexes(ElementInterface $element, FieldLayout $fieldLayout, &$nonTranslatableFields = null, &$otherContentModels = null)
    {
        /** @var Element $element */
        $searchKeywordsByLocale = [];

        foreach ($fieldLayout->getFields() as $field) {
            /** @var Field $field */
            // Set the keywords for the content's locale
            $fieldValue = $element->getFieldValue($field->handle);
            $fieldSearchKeywords = $field->getSearchKeywords($fieldValue, $element);
            $searchKeywordsByLocale[$element->locale][$field->id] = $fieldSearchKeywords;

            // Should we queue up the other locales' new keywords too?
            if (isset($nonTranslatableFields[$field->id])) {
                foreach ($otherContentModels as $otherContentModel) {
                    $searchKeywordsByLocale[$otherContentModel->locale][$field->id] = $fieldSearchKeywords;
                }
            }
        }

        foreach ($searchKeywordsByLocale as $localeId => $keywords) {
            Craft::$app->getSearch()->indexElementFields($element->id, $localeId, $keywords);
        }
    }

    /**
     * Removes the column prefixes from a given row.
     *
     * @param array $row
     *
     * @return array
     */
    private function _removeColumnPrefixesFromRow($row)
    {
        $fieldColumnPrefixLength = strlen($this->fieldColumnPrefix);

        foreach ($row as $column => $value) {
            if (strncmp($column, $this->fieldColumnPrefix,
                    $fieldColumnPrefixLength) === 0
            ) {
                $fieldHandle = substr($column, $fieldColumnPrefixLength);
                $row[$fieldHandle] = $value;
                unset($row[$column]);
            }
        }

        return $row;
    }
}
