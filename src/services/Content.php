<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\errors\Exception;
use craft\app\events\ElementEvent;
use craft\app\helpers\DbHelper;
use craft\app\helpers\ModelHelper;
use craft\app\models\BaseElementModel;
use craft\app\models\Content as ContentModel;
use craft\app\models\FieldLayout as FieldLayoutModel;
use yii\base\Component;

/**
 * Class Content service.
 *
 * An instance of the Content service is globally accessible in Craft via [[Application::content `Craft::$app->content`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	public $contentTable = 'content';

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
	 * Returns the content model for a given element.
	 *
	 * @param BaseElementModel $element The element whose content we're looking for.
	 *
	 * @return ContentModel|null The element's content or `null` if no content has been saved for the element.
	 */
	public function getContent(BaseElementModel $element)
	{
		if (!$element->id || !$element->locale)
		{
			return;
		}

		$originalContentTable      = $this->contentTable;
		$originalFieldColumnPrefix = $this->fieldColumnPrefix;
		$originalFieldContext      = $this->fieldContext;

		$this->contentTable        = $element->getContentTable();
		$this->fieldColumnPrefix   = $element->getFieldColumnPrefix();
		$this->fieldContext        = $element->getFieldContext();

		$row = Craft::$app->getDb()->createCommand()
			->from($this->contentTable)
			->where([
				'elementId' => $element->id,
				'locale'    => $element->locale
			])
			->queryRow();

		if ($row)
		{
			$row = $this->_removeColumnPrefixesFromRow($row);
			$content = new ContentModel($row);
		}
		else
		{
			$content = null;
		}

		$this->contentTable        = $originalContentTable;
		$this->fieldColumnPrefix   = $originalFieldColumnPrefix;
		$this->fieldContext        = $originalFieldContext;

		return $content;
	}

	/**
	 * Instantiates a new content model for a given element.
	 *
	 * @param BaseElementModel $element The element for which we should create a new content model.
	 *
	 * @return ContentModel The new content model.
	 */
	public function createContent(BaseElementModel $element)
	{
		$originalContentTable      = $this->contentTable;
		$originalFieldColumnPrefix = $this->fieldColumnPrefix;
		$originalFieldContext      = $this->fieldContext;

		$this->contentTable        = $element->getContentTable();
		$this->fieldColumnPrefix   = $element->getFieldColumnPrefix();
		$this->fieldContext        = $element->getFieldContext();

		$content = new ContentModel();
		$content->elementId = $element->id;
		$content->locale = $element->locale;

		$this->contentTable        = $originalContentTable;
		$this->fieldColumnPrefix   = $originalFieldColumnPrefix;
		$this->fieldContext        = $originalFieldContext;

		return $content;
	}

	/**
	 * Saves an element's content.
	 *
	 * @param BaseElementModel $element            The element whose content we're saving.
	 * @param bool             $validate           Whether the element's content should be validated first.
	 * @param bool             $updateOtherLocales Whether any non-translatable fields' values should be copied to the
	 *                                             element's other locales.
	 *
	 * @throws Exception
	 * @return bool Whether the content was saved successfully. If it wasn't, any validation errors will be saved on the
	 *              element and its content model.
	 */
	public function saveContent(BaseElementModel $element, $validate = true, $updateOtherLocales = true)
	{
		if (!$element->id)
		{
			throw new Exception(Craft::t('Cannot save the content of an unsaved element.'));
		}

		$originalContentTable      = $this->contentTable;
		$originalFieldColumnPrefix = $this->fieldColumnPrefix;
		$originalFieldContext      = $this->fieldContext;

		$this->contentTable        = $element->getContentTable();
		$this->fieldColumnPrefix   = $element->getFieldColumnPrefix();
		$this->fieldContext        = $element->getFieldContext();

		$content = $element->getContent();

		if (!$validate || $this->validateContent($element))
		{
			$this->_saveContentRow($content);

			$fieldLayout = $element->getFieldLayout();

			if ($fieldLayout)
			{
				if ($updateOtherLocales && Craft::$app->isLocalized())
				{
					$this->_duplicateNonTranslatableFieldValues($element, $content, $fieldLayout, $nonTranslatableFields, $otherContentModels);
				}

				$this->_updateSearchIndexes($element, $content, $fieldLayout, $nonTranslatableFields, $otherContentModels);
			}

			$success = true;
		}
		else
		{
			$element->addErrors($content->getErrors());
			$success = false;
		}

		$this->contentTable        = $originalContentTable;
		$this->fieldColumnPrefix   = $originalFieldColumnPrefix;
		$this->fieldContext        = $originalFieldContext;

		return $success;
	}

	/**
	 * Validates some content with a given field layout.
	 *
	 * @param BaseElementModel $element The element whose content should be validated.
	 *
	 * @return bool Whether the element's content validates.
	 */
	public function validateContent(BaseElementModel $element)
	{
		$elementType = Craft::$app->elements->getElementType($element->getElementType());
		$fieldLayout = $element->getFieldLayout();
		$content     = $element->getContent();

		// Set the required fields from the layout
		$attributesToValidate = ['id', 'elementId', 'locale'];
		$requiredFields = [];

		if ($elementType->hasTitles())
		{
			$requiredFields[] = 'title';
			$attributesToValidate[] = 'title';
		}

		if ($fieldLayout)
		{
			foreach ($fieldLayout->getFields() as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();

				if ($field)
				{
					$attributesToValidate[] = $field->handle;

					if ($fieldLayoutField->required)
					{
						$requiredFields[] = $field->id;
					}
				}
			}
		}

		if ($requiredFields)
		{
			$content->setRequiredFields($requiredFields);
		}

		return $content->validate($attributesToValidate);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Saves a content model to the database.
	 *
	 * @param ContentModel $content
	 *
	 * @return bool
	 */
	private function _saveContentRow(ContentModel $content)
	{
		$values = [
			'id'        => $content->id,
			'elementId' => $content->elementId,
			'locale'    => $content->locale,
		];

		$excludeColumns = array_keys($values);
		$excludeColumns = array_merge($excludeColumns, array_keys(DbHelper::getAuditColumnConfig()));

		$fullContentTableName = Craft::$app->getDb()->addTablePrefix($this->contentTable);
		$contentTableSchema = Craft::$app->getDb()->schema->getTable($fullContentTableName);

		foreach ($contentTableSchema->columns as $columnSchema)
		{
			if ($columnSchema->allowNull && !in_array($columnSchema->name, $excludeColumns))
			{
				$values[$columnSchema->name] = null;
			}
		}

		// If the element type has titles, than it's required and will be set. Otherwise, no need to include it (it
		// might not even be a real column if this isn't the 'content' table).
		if ($content->title)
		{
			$values['title'] = $content->title;
		}

		foreach (Craft::$app->fields->getFieldsWithContent() as $field)
		{
			$handle = $field->handle;
			$value = $content->$handle;
			$values[$this->fieldColumnPrefix.$field->handle] = ModelHelper::packageAttributeValue($value, true);
		}

		$isNewContent = !$content->id;

		if (!$isNewContent)
		{
			$affectedRows = Craft::$app->getDb()->createCommand()->update($this->contentTable, $values, ['id' => $content->id]);
		}
		else
		{
			$affectedRows = Craft::$app->getDb()->createCommand()->insert($this->contentTable, $values);
		}

		if ($affectedRows)
		{
			if ($isNewContent)
			{
				// Set the new ID
				$content->id = Craft::$app->getDb()->getLastInsertID();
			}

			// Fire an 'afterSaveContent' event
			$this->trigger(static::EVENT_AFTER_SAVE_CONTENT, new ElementEvent([
				'content' => $content
			]));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Copies the new values of any non-translatable fields across the element's
	 * other locales.
	 *
	 * @param BaseElementModel $element
	 * @param ContentModel     $content
	 * @param FieldLayoutModel $fieldLayout
	 * @param array            &$nonTranslatableFields
	 * @param array            &$otherContentModels
	 *
	 * @return null
	 */
	private function _duplicateNonTranslatableFieldValues(BaseElementModel $element, ContentModel $content, FieldLayoutModel $fieldLayout, &$nonTranslatableFields, &$otherContentModels)
	{
		// Get all of the non-translatable fields
		$nonTranslatableFields = [];

		foreach ($fieldLayout->getFields() as $fieldLayoutField)
		{
			$field = $fieldLayoutField->getField();

			if ($field && !$field->translatable)
			{
				if ($field->hasContentColumn())
				{
					$nonTranslatableFields[$field->id] = $field;
				}
			}
		}

		if ($nonTranslatableFields)
		{
			// Get the other locales' content
			$rows = Craft::$app->getDb()->createCommand()
				->from($this->contentTable)
				->where(
					['and', 'elementId = :elementId', 'locale != :locale'],
					[':elementId' => $element->id, ':locale' => $content->locale])
				->queryAll();

			// Remove the column prefixes
			foreach ($rows as $i => $row)
			{
				$rows[$i] = $this->_removeColumnPrefixesFromRow($row);
			}

			$otherContentModels = ContentModel::populateModels($rows);

			foreach ($otherContentModels as $otherContentModel)
			{
				foreach ($nonTranslatableFields as $field)
				{
					$handle = $field->handle;
					$otherContentModel->$handle = $content->$handle;
				}

				$this->_saveContentRow($otherContentModel);
			}
		}
	}

	/**
	 * Updates the search indexes based on the new content values.
	 *
	 * @param BaseElementModel $element
	 * @param ContentModel     $content
	 * @param FieldLayoutModel $fieldLayout
	 * @param array|null       &$nonTranslatableFields
	 * @param array|null       &$otherContentModels
	 *
	 * @return null
	 */
	private function _updateSearchIndexes(BaseElementModel $element, ContentModel $content, FieldLayoutModel $fieldLayout, &$nonTranslatableFields = null, &$otherContentModels = null)
	{
		$searchKeywordsByLocale = [];

		foreach ($fieldLayout->getFields() as $fieldLayoutField)
		{
			$field = $fieldLayoutField->getField();

			if ($field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType)
				{
					$fieldType->element = $element;

					$handle = $field->handle;

					// Set the keywords for the content's locale
					$fieldSearchKeywords = $fieldType->getSearchKeywords($element->getFieldValue($handle));
					$searchKeywordsByLocale[$content->locale][$field->id] = $fieldSearchKeywords;

					// Should we queue up the other locales' new keywords too?
					if (isset($nonTranslatableFields[$field->id]))
					{
						foreach ($otherContentModels as $otherContentModel)
						{
							$searchKeywordsByLocale[$otherContentModel->locale][$field->id] = $fieldSearchKeywords;
						}
					}
				}
			}
		}

		foreach ($searchKeywordsByLocale as $localeId => $keywords)
		{
			Craft::$app->search->indexElementFields($element->id, $localeId, $keywords);
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

		foreach ($row as $column => $value)
		{
			if (strncmp($column, $this->fieldColumnPrefix, $fieldColumnPrefixLength) === 0)
			{
				$fieldHandle = substr($column, $fieldColumnPrefixLength);
				$row[$fieldHandle] = $value;
				unset($row[$column]);
			}
		}

		return $row;
	}
}
