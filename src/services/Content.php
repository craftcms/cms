<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\db\Query;
use craft\app\errors\Exception;
use craft\app\events\ContentEvent;
use craft\app\helpers\DbHelper;
use craft\app\models\Content as ContentModel;
use craft\app\models\FieldLayout as FieldLayoutModel;
use yii\base\Component;

/**
 * Class Content service.
 *
 * An instance of the Content service is globally accessible in Craft via [[Application::content `Craft::$app->getContent()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Content extends Component
{
	// Constants
	// =========================================================================

	/**
     * @event ContentEvent The event that is triggered after an element's content is saved.
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
	 * Returns the content model for a given element.
	 *
	 * @param ElementInterface $element The element whose content we're looking for.
	 *
	 * @return ContentModel|null The element's content or `null` if no content has been saved for the element.
	 */
	public function getContent(ElementInterface $element)
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

		$row = (new Query())
			->from($this->contentTable)
			->where([
				'elementId' => $element->id,
				'locale'    => $element->locale
			])
			->one();

		if ($row)
		{
			$row = $this->_removeColumnPrefixesFromRow($row);
			$content = ContentModel::create($row);
			$content->element = $element;
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
	 * @param ElementInterface $element The element for which we should create a new content model.
	 *
	 * @return ContentModel The new content model.
	 */
	public function createContent(ElementInterface $element)
	{
		$originalContentTable      = $this->contentTable;
		$originalFieldColumnPrefix = $this->fieldColumnPrefix;
		$originalFieldContext      = $this->fieldContext;

		$this->contentTable        = $element->getContentTable();
		$this->fieldColumnPrefix   = $element->getFieldColumnPrefix();
		$this->fieldContext        = $element->getFieldContext();

		$content = new ContentModel();
		$content->element = $element;
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
	 * @param ElementInterface $element            The element whose content we're saving.
	 * @param bool             $validate           Whether the element's content should be validated first.
	 * @param bool             $updateOtherLocales Whether any non-translatable fields' values should be copied to the
	 *                                             element's other locales.
	 *
	 * @throws Exception
	 * @return bool Whether the content was saved successfully. If it wasn't, any validation errors will be saved on the
	 *              element and its content model.
	 */
	public function saveContent(ElementInterface $element, $validate = true, $updateOtherLocales = true)
	{
		if (!$element->id)
		{
			throw new Exception(Craft::t('app', 'Cannot save the content of an unsaved element.'));
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
	 * @param ElementInterface $element The element whose content should be validated.
	 *
	 * @return bool Whether the element's content validates.
	 */
	public function validateContent(ElementInterface $element)
	{
		$fieldLayout = $element->getFieldLayout();
		$content     = $element->getContent();

		// Set the required fields from the layout
		$attributesToValidate = ['id', 'elementId', 'locale'];
		$requiredFields = [];

		if ($element::hasTitles())
		{
			$requiredFields[] = 'title';
			$attributesToValidate[] = 'title';
		}

		if ($fieldLayout)
		{
			foreach ($fieldLayout->getFields() as $field)
			{
				$attributesToValidate[] = $field->handle;

				if ($field->required)
				{
					$requiredFields[] = $field->id;
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

		$contentTableSchema = Craft::$app->getDb()->getTableSchema($this->contentTable);

		foreach ($contentTableSchema->getColumnNames() as $columnName)
		{
			if (!in_array($columnName, $excludeColumns))
			{
				$columnSchema = $contentTableSchema->getColumn($columnName);

				if ($columnSchema->allowNull)
				{
					$values[$columnName] = null;
				}
			}
		}

		// If the element type has titles, than it's required and will be set. Otherwise, no need to include it (it
		// might not even be a real column if this isn't the 'content' table).
		if ($content->title)
		{
			$values['title'] = $content->title;
		}

		foreach (Craft::$app->getFields()->getFieldsWithContent() as $field)
		{
			$handle = $field->handle;
			$value = $content->$handle;
			$values[$this->fieldColumnPrefix.$field->handle] = DbHelper::prepValue($value);
		}

		$isNewContent = !$content->id;

		if (!$isNewContent)
		{
			$affectedRows = Craft::$app->getDb()->createCommand()->update($this->contentTable, $values, ['id' => $content->id])->execute();
		}
		else
		{
			$affectedRows = Craft::$app->getDb()->createCommand()->insert($this->contentTable, $values)->execute();
		}

		if ($affectedRows)
		{
			if ($isNewContent)
			{
				// Set the new ID
				$content->id = Craft::$app->getDb()->getLastInsertID();
			}

			// Fire an 'afterSaveContent' event
			$this->trigger(static::EVENT_AFTER_SAVE_CONTENT, new ContentEvent([
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
	 * @param ElementInterface $element
	 * @param ContentModel     $content
	 * @param FieldLayoutModel $fieldLayout
	 * @param array            &$nonTranslatableFields
	 * @param array            &$otherContentModels
	 *
	 * @return null
	 */
	private function _duplicateNonTranslatableFieldValues(ElementInterface $element, ContentModel $content, FieldLayoutModel $fieldLayout, &$nonTranslatableFields, &$otherContentModels)
	{
		// Get all of the non-translatable fields
		$nonTranslatableFields = [];

		foreach ($fieldLayout->getFields() as $field)
		{
			if (!$field->translatable && $field->hasContentColumn())
			{
				$nonTranslatableFields[$field->id] = $field;
			}
		}

		if ($nonTranslatableFields)
		{
			// Get the other locales' content
			$otherContentModels = (new Query())
				->from($this->contentTable)
				->where(
					['and', 'elementId = :elementId', 'locale != :locale'],
					[':elementId' => $element->id, ':locale' => $content->locale])
				->all();

			// Remove the column prefixes
			foreach ($otherContentModels as $key => $value)
			{
				$otherContentModels[$key] = ContentModel::create($this->_removeColumnPrefixesFromRow($value));
			}

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
	 * @param ElementInterface $element
	 * @param ContentModel     $content
	 * @param FieldLayoutModel $fieldLayout
	 * @param array|null       &$nonTranslatableFields
	 * @param array|null       &$otherContentModels
	 *
	 * @return null
	 */
	private function _updateSearchIndexes(ElementInterface $element, ContentModel $content, FieldLayoutModel $fieldLayout, &$nonTranslatableFields = null, &$otherContentModels = null)
	{
		$searchKeywordsByLocale = [];

		foreach ($fieldLayout->getFields() as $field)
		{
			// Set the keywords for the content's locale
			$fieldValue = $element->getFieldValue($field->handle);
			$fieldSearchKeywords = $field->getSearchKeywords($fieldValue, $element);
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

		foreach ($searchKeywordsByLocale as $localeId => $keywords)
		{
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
