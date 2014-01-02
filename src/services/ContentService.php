<?php
namespace Craft;

/**
 *
 */
class ContentService extends BaseApplicationComponent
{
	public $contentTable = 'content';
	public $fieldColumnPrefix = 'field_';
	public $fieldContext = 'global';

	/**
	 * Returns the content model for a given element.
	 *
	 * @param BaseElementModel $elementId
	 * @return ContentModel|null
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

		if ($localeId)
		{
			$conditions['locale'] = $localeId;
		}

		$row = craft()->db->createCommand()
			->from($this->contentTable)
			->where(array(
				'elementId' => $element->id,
				'locale'    => $element->locale
			))
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
	 * Creates a new content model for a given element.
	 *
	 * @param BaseElementModel $element
	 * @return ContentModel
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
	 * @param BaseElementModel $element
	 * @param bool             $validate
	 * @param bool             $updateOtherLocales
	 * @throws Exception
	 * @return bool
	 */
	public function saveContent(BaseElementModel $element, $validate = true, $updateOtherLocales = true)
	{
		if (!$element->id)
		{
			throw new Exception(Craft::t('Cannot save the content of an unsaved element.'));
		}

		if (!$validate || $this->validateContent($element))
		{
			$content = $element->getContent();

			$this->_saveContentRow($content);
			$this->_postSaveOperations($element, $content, $updateOtherLocales);
			return true;
		}
		else
		{
			$element->addErrors($content->getErrors());
			return false;
		}
	}

	/**
	 * Validates some content with a given field layout.
	 *
	 * @param BaseElementModel $element
	 * @return bool
	 */
	public function validateContent(BaseElementModel $element)
	{
		$elementType = craft()->elements->getElementType($element->getElementType());
		$fieldLayout = $element->getFieldLayout();
		$content     = $element->getContent();

		// Set the required fields from the layout
		$attributesToValidate = array('id', 'elementId', 'locale');
		$requiredFields = array();

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

	/**
	 * Fires an 'onSaveContent' event.
	 *
	 * @param Event $event
	 */
	public function onSaveContent(Event $event)
	{
		$this->raiseEvent('onSaveContent', $event);
	}

	/**
	 * Saves a content model to the database.
	 *
	 * @access private
	 * @param ContentModel $content
	 * @return bool
	 */
	private function _saveContentRow(ContentModel $content)
	{
		$values = array(
			'id'        => $content->id,
			'elementId' => $content->elementId,
			'locale'    => $content->locale,
		);

		// If the element type has titles, than it's required and will be set.
		// Otherwise, no need to include it (it might not even be a real column if this isn't the 'content' table).
		if ($content->title)
		{
			$values['title'] = $content->title;
		}

		foreach (craft()->fields->getFieldsWithContent() as $field)
		{
			$handle = $field->handle;
			$value = $content->$handle;
			$values[$this->fieldColumnPrefix.$field->handle] = ModelHelper::packageAttributeValue($value, true);
		}

		$isNewContent = !$content->id;

		if (!$isNewContent)
		{
			$affectedRows = craft()->db->createCommand()->update($this->contentTable, $values, array('id' => $content->id));
		}
		else
		{
			$affectedRows = craft()->db->createCommand()->insert($this->contentTable, $values);
		}

		if ($affectedRows)
		{
			if ($isNewContent)
			{
				// Set the new ID
				$content->id = craft()->db->getLastInsertID();
			}

			// Fire an 'onSaveContent' event
			$this->onSaveContent(new Event($this, array(
				'content'      => $content,
				'isNewContent' => $isNewContent
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Performs post-save element operations, such as calling all fieldtypes' onAfterElementSave() methods.
	 *
	 * @access private
	 * @param BaseElementModel $element
	 * @param ContentModel     $content
	 * @param bool             $updateOtherLocales
	 */
	private function _postSaveOperations(BaseElementModel $element, ContentModel $content, $updateOtherLocales)
	{
		if ($updateOtherLocales && !craft()->hasPackage(CraftPackage::Localize))
		{
			$updateOtherLocales = false;
		}

		$fieldLayout = $element->getFieldLayout();

		// Copy the non-trasnlatable field values over to the other locales

		if (craft()->hasPackage(CraftPackage::Localize))
		{
			// Get all of the non-translatable fields
			$fieldsWithDuplicateContent = array();

			if ($fieldLayout)
			{
				foreach ($fieldLayout->getFields() as $fieldLayoutField)
				{
					$field = $fieldLayoutField->getField();

					if ($field && !$field->translatable)
					{
						$fieldType = $field->getFieldType();

						if ($fieldType && $fieldType->defineContentAttribute())
						{
							$fieldsWithDuplicateContent[$field->id] = $field;
						}
					}
				}
			}

			if ($updateOtherLocales)
			{
				// Get the other locales' content
				$rows = craft()->db->createCommand()
					->from($this->contentTable)
					->where(
						array('and', 'elementId = :elementId', 'locale != :locale'),
						array(':elementId' => $element->id, ':locale' => $content->locale))
					->queryAll();

				// Remove the column prefixes
				foreach ($rows as $i => $row)
				{
					$rows[$i] = $this->_removeColumnPrefixesFromRow($row);
				}

				$otherContentModels = ContentModel::populateModels($rows);

				if ($fieldsWithDuplicateContent && $otherContentModels)
				{
					// Copy the dupliacte content over to the other locases
					foreach ($fieldsWithDuplicateContent as $field)
					{
						$handle = $field->handle;

						foreach ($otherContentModels as $otherContentModel)
						{
							$otherContentModel->$handle = $content->$handle;
						}
					}

					foreach ($otherContentModels as $otherContentModel)
					{
						$this->_saveContentRow($otherContentModel);
					}
				}
			}
		}

		// Call all fieldtypes' onAfterElementSave() functions now that all of the content saved for all locales
		// and also update the search indexes

		$searchKeywordsByLocale = array();

		if ($fieldLayout)
		{
			foreach ($fieldLayout->getFields() as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();

				if ($field && !$field->translatable)
				{
					$fieldType = $field->getFieldType();

					if ($fieldType)
					{
						$fieldType->element = $element;

						// Call onAfterElementSave()
						$fieldType->onAfterElementSave();

						$handle = $field->handle;

						// Set the keywords for the content's locale
						$fieldSearchKeywords = $fieldType->getSearchKeywords($content->$handle);
						$searchKeywordsByLocale[$content->locale][$field->id] = $fieldSearchKeywords;

						// Should we queue up the other locales' new keywords too?
						if ($updateOtherLocales)
						{
							if ($otherContentModels && in_array($field->id, array_keys($fieldsWithDuplicateContent)))
							{
								foreach ($otherContentModels as $otherContentModel)
								{
									$searchKeywordsByLocale[$otherContentModel->locale][$field->id] = $fieldSearchKeywords;
								}
							}
						}
					}
				}
			}
		}

		foreach ($searchKeywordsByLocale as $localeId => $keywords)
		{
			craft()->search->indexElementFields($element->id, $localeId, $keywords);
		}
	}

	/**
	 * Removes the column prefixes from a given row.
	 *
	 * @access private
	 * @param array $row
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
