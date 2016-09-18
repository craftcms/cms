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
use craft\app\db\Query;
use craft\app\events\ElementContentEvent;
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
     * @event ElementContentEvent The event that is triggered before an element's content is saved.
     */
    const EVENT_BEFORE_SAVE_CONTENT = 'beforeSaveContent';

    /**
     * @event ElementContentEvent The event that is triggered after an element's content is saved.
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
     * @return array|false The element's content row values, or false if the row could not be found
     */
    public function getContentRow(ElementInterface $element)
    {
        /** @var Element $element */
        if (!$element->id || !$element->siteId) {
            return false;
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
                'siteId' => $element->siteId
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
     * @param ElementInterface $element       The element whose content we're saving.
     * @param boolean          $runValidation Whether the element's content should be validated first.
     *
     * @return boolean Whether the content was saved successfully. If it wasn't, any validation errors will be saved on the
     *                 element and its content model.
     * @throws Exception if $element has not been saved yet
     */
    public function saveContent(ElementInterface $element, $runValidation = true)
    {
        /** @var Element $element */
        if (!$element->id) {
            throw new Exception('Cannot save the content of an unsaved element.');
        }

        if ($runValidation && $this->validateContent($element)) {
             Craft::info('Content not saved due to validation error.', __METHOD__);

             return false;
        }

        $originalContentTable = $this->contentTable;
        $originalFieldColumnPrefix = $this->fieldColumnPrefix;
        $originalFieldContext = $this->fieldContext;

        $this->contentTable = $element->getContentTable();
        $this->fieldColumnPrefix = $element->getFieldColumnPrefix();
        $this->fieldContext = $element->getFieldContext();

        // Fire a 'beforeSaveContent' event
        $this->trigger(self::EVENT_BEFORE_SAVE_CONTENT, new ElementContentEvent([
            'element' => $element
        ]));

        // Prepare the data to be saved
        $values = [
            'elementId' => $element->id,
            'siteId' => $element->siteId
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

        if ($fieldLayout) {
            $this->_updateSearchIndexes($element, $fieldLayout);
        }

        // Fire an 'afterSaveContent' event
        $this->trigger(self::EVENT_AFTER_SAVE_CONTENT, new ElementContentEvent([
            'element' => $element
        ]));

        $this->contentTable = $originalContentTable;
        $this->fieldColumnPrefix = $originalFieldColumnPrefix;
        $this->fieldContext = $originalFieldContext;

        return true;
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
                        'value' => is_array($value) ? 'array()' : (is_object($value) ? get_class($value) : $value),
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
     * Updates the search indexes based on the new content values.
     *
     * @param ElementInterface $element
     * @param FieldLayout      $fieldLayout
     *
     * @return void
     */
    private function _updateSearchIndexes(ElementInterface $element, FieldLayout $fieldLayout)
    {
        /** @var Element $element */
        $searchKeywordsBySiteId = [];

        foreach ($fieldLayout->getFields() as $field) {
            /** @var Field $field */
            // Set the keywords for the content's site
            $fieldValue = $element->getFieldValue($field->handle);
            $fieldSearchKeywords = $field->getSearchKeywords($fieldValue, $element);
            $searchKeywordsBySiteId[$element->siteId][$field->id] = $fieldSearchKeywords;
        }

        foreach ($searchKeywordsBySiteId as $siteId => $keywords) {
            Craft::$app->getSearch()->indexElementFields($element->id, $siteId, $keywords);
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
