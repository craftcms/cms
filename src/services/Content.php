<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use craft\events\ElementContentEvent;
use craft\helpers\Db;
use craft\models\FieldLayout;
use yii\base\Component;
use yii\base\Exception;

/**
 * Content service.
 * An instance of the Content service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getContent()|`Craft::$app->content`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
     * @return array|null The element's content row values, or null if the row could not be found
     */
    public function getContentRow(ElementInterface $element)
    {
        /** @var Element $element */
        if (!$element->id || !$element->siteId) {
            return null;
        }

        $originalContentTable = $this->contentTable;
        $originalFieldColumnPrefix = $this->fieldColumnPrefix;
        $originalFieldContext = $this->fieldContext;

        $this->contentTable = $element->getContentTable();
        $this->fieldColumnPrefix = $element->getFieldColumnPrefix();
        $this->fieldContext = $element->getFieldContext();

        $row = (new Query())
            ->from([$this->contentTable])
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
     */
    public function populateElementContent(ElementInterface $element)
    {
        /** @var Element $element */
        // Make sure the element has content
        if (!$element->hasContent()) {
            return;
        }

        if ($row = $this->getContentRow($element)) {
            $element->contentId = $row['id'];

            if ($element->hasTitles() && isset($row['title'])) {
                $element->title = $row['title'];
            }

            $fieldLayout = $element->getFieldLayout();

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
     * @param ElementInterface $element The element whose content we're saving.
     * @return bool Whether the content was saved successfully. If it wasn't, any validation errors will be saved on the
     * element and its content model.
     * @throws Exception if $element has not been saved yet
     */
    public function saveContent(ElementInterface $element): bool
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

        // Fire a 'beforeSaveContent' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_CONTENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_CONTENT, new ElementContentEvent([
                'element' => $element
            ]));
        }

        // Prepare the data to be saved
        $values = [
            'elementId' => $element->id,
            'siteId' => $element->siteId
        ];
        if ($element->hasTitles() && ($title = (string)$element->title) !== '') {
            $values['title'] = $title;
        }
        $fieldLayout = $element->getFieldLayout();
        if ($fieldLayout) {
            foreach ($fieldLayout->getFields() as $field) {
                /** @var Field $field */
                if ($field::hasContentColumn()) {
                    $column = $this->fieldColumnPrefix . $field->handle;
                    $values[$column] = Db::prepareValueForDb($field->serializeValue($element->getFieldValue($field->handle), $element));
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
            $element->contentId = Craft::$app->getDb()->getLastInsertID($this->contentTable);
        }

        if ($fieldLayout) {
            $this->_updateSearchIndexes($element, $fieldLayout);
        }

        // Fire an 'afterSaveContent' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CONTENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CONTENT, new ElementContentEvent([
                'element' => $element
            ]));
        }

        $this->contentTable = $originalContentTable;
        $this->fieldColumnPrefix = $originalFieldColumnPrefix;
        $this->fieldContext = $originalFieldContext;

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Updates the search indexes based on the new content values.
     *
     * @param ElementInterface $element
     * @param FieldLayout $fieldLayout
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
     * @return array
     */
    private function _removeColumnPrefixesFromRow(array $row): array
    {
        foreach ($row as $column => $value) {
            if (strpos($column, $this->fieldColumnPrefix) === 0) {
                $fieldHandle = substr($column, strlen($this->fieldColumnPrefix));
                $row[$fieldHandle] = $value;
                unset($row[$column]);
            } else if (!in_array($column, ['id', 'elementId', 'title', 'dateCreated', 'dateUpdated', 'uid', 'siteId'], true)) {
                unset($row[$column]);
            }
        }

        return $row;
    }
}
