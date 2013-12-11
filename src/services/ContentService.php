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
	 * Returns the content model for a given element and locale.
	 *
	 * @param int $elementId
	 * @param string|null $localeId
	 * @return ContentModel|null
	 */
	public function getElementContent($elementId, $localeId = null)
	{
		$conditions = array('elementId' => $elementId);

		if ($localeId)
		{
			$conditions['locale'] = $localeId;
		}

		$row = craft()->db->createCommand()
			->from($this->contentTable)
			->where($conditions)
			->queryRow();

		if ($row)
		{
			$row = $this->_removeColumnPrefixesFromRow($row);

			return new ContentModel($row);
		}
	}

	/**
	 * Saves an element's content.
	 *
	 * This is just a wrapper for prepElementContentForSave(), saveContent(), and postSaveOperations().
	 * It should only be used when an element's content is saved separately from its other attributes.
	 *
	 * @param BaseElementModel $element
	 * @param FieldLayoutModel $fieldLayout
	 * @param bool             $validate
	 * @throws Exception
	 * @return bool
	 */
	public function saveElementContent(BaseElementModel $element, FieldLayoutModel $fieldLayout, $validate = true)
	{
		if (!$element->id)
		{
			throw new Exception(Craft::t('Cannot save the content of an unsaved element.'));
		}

		$content = $this->prepElementContentForSave($element, $fieldLayout, $validate);

		if (!$validate || $this->validateElementContent($element, $fieldLayout))
		{
			$this->saveContent($content);
			$this->postSaveOperations($element, $content);
			return true;
		}
		else
		{
			$element->addErrors($content->getErrors());
			return false;
		}
	}

	/**
	 * Prepares an element's content for being saved to the database.
	 *
	 * @param BaseElementModel $element
	 * @param FieldLayoutModel $fieldLayout
	 * @param bool             $setRequiredFields
	 * @return ContentModel
	 */
	public function prepElementContentForSave(BaseElementModel $element, FieldLayoutModel $fieldLayout, $setRequiredFields = true)
	{
		$content = $element->getContent();

		if ($setRequiredFields)
		{
			// Set the required fields from the layout
			$requiredFields = array();

			$elementTypeClass = $element->getElementType();
			$elementType = craft()->elements->getElementType($elementTypeClass);

			if ($elementType->hasTitles())
			{
				$requiredFields[] = 'title';
			}
		}

		foreach ($fieldLayout->getFields() as $fieldLayoutField)
		{
			if ($setRequiredFields && $fieldLayoutField->required)
			{
				$requiredFields[] = $fieldLayoutField->fieldId;
			}

			$field = $fieldLayoutField->getField();

			if ($field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType)
				{
					$fieldType->element = $element;

					$handle = $field->handle;
					$content->$handle = $fieldType->prepValueFromPost($content->$handle);
				}
			}
		}

		if ($setRequiredFields && $requiredFields)
		{
			$content->setRequiredFields($requiredFields);
		}

		return $content;
	}

	/**
	 * Validates some content with a given field layout.
	 *
	 * @param BaseElementModel $element
	 * @param FieldLayoutModel $fieldLayout
	 * @return bool
	 */
	public function validateElementContent(BaseElementModel $element, FieldLayoutModel $fieldLayout)
	{
		$content = $element->getContent();

		// Set the required fields from the layout
		$attributesToValidate = array('id', 'elementId', 'locale');
		$requiredFields = array();

		$elementTypeClass = $element->getElementType();
		$elementType = craft()->elements->getElementType($elementTypeClass);

		if ($elementType->hasTitles())
		{
			$requiredFields[] = 'title';
			$attributesToValidate[] = 'title';
		}

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

		if ($requiredFields)
		{
			$content->setRequiredFields($requiredFields);
		}

		return $content->validate($attributesToValidate);
	}

	/**
	 * Saves a content model to the database.
	 *
	 * @param ContentModel $content
	 * @return bool
	 */
	public function saveContent(ContentModel $content)
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
			$affectedRows = craft()->db->createCommand()
				->update($this->contentTable, $values, array('id' => $content->id));
		}
		else
		{
			$affectedRows = craft()->db->createCommand()
				->insert($this->contentTable, $values);

			if ($affectedRows)
			{
				// Set the new ID
				$content->id = craft()->db->getLastInsertID();
			}
		}

		if ($affectedRows)
		{
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
	 * Fires an 'onSaveContent' event.
	 *
	 * @param Event $event
	 */
	public function onSaveContent(Event $event)
	{
		$this->raiseEvent('onSaveContent', $event);
	}

	/**
	 * Performs post-save element operations, such as calling all fieldtypes' onAfterElementSave() methods.
	 *
	 * @param BaseElementModel $element
	 * @param ContentModel $content
	 */
	public function postSaveOperations(BaseElementModel $element, ContentModel $content)
	{
		// Get all of the fieldtypes
		$fields = craft()->fields->getAllFields();
		$fieldsWithDuplicateContent = array();

		foreach ($fields as $field)
		{
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				$fieldType->element = $element;

				if (!$field->translatable && $fieldType->defineContentAttribute())
				{
					$fieldsWithDuplicateContent[$field->id] = $field;
				}
			}
		}

		// Are we dealing with other locales as well?
		if (craft()->hasPackage(CraftPackage::Localize))
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

			if ($otherContentModels)
			{
				foreach ($fieldsWithDuplicateContent as $field)
				{
					$handle = $field->handle;

					// Copy the content over!
					foreach ($otherContentModels as $otherContentModel)
					{
						$otherContentModel->$handle = $content->$handle;
					}
				}

				foreach ($otherContentModels as $otherContentModel)
				{
					$this->saveContent($otherContentModel);
				}
			}
		}
		else
		{
			$otherContentModels = null;
		}

		// Now that all of the content saved for all locales,
		// call all fieldtypes' onAfterElementSave() functions
		foreach ($fields as $field)
		{
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				$fieldType->element = $element;
				$fieldType->onAfterElementSave();
			}
		}

		// Update the search keyword indexes
		$searchKeywordsByLocale = array();

		foreach ($fields as $field)
		{
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				$fieldType->element = $element;
				$handle = $field->handle;

				// Set the keywords for the content's locale
				$fieldSearchKeywords = $fieldType->getSearchKeywords($element->$handle);
				$searchKeywordsByLocale[$content->locale][$field->id] = $fieldSearchKeywords;

				// Should we queue up the other locales' new keywords too?
				if ($otherContentModels && in_array($field->id, array_keys($fieldsWithDuplicateContent)))
				{
					foreach ($otherContentModels as $otherContentModel)
					{
						$searchKeywordsByLocale[$otherContentModel->locale][$field->id] = $fieldSearchKeywords;
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
