<?php
namespace Craft;

/**
 *
 */
class ContentService extends BaseApplicationComponent
{
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
			->from('content')
			->where($conditions)
			->queryRow();

		if ($row)
		{
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
	 * @param string|null $localeId
	 */
	public function saveElementContent(BaseElementModel $element, FieldLayoutModel $fieldLayout, $localeId = null)
	{
		if (!$element->id)
		{
			throw new Exception(Craft::t('Cannot save the content of an unsaved element.'));
		}

		$content = $this->prepElementContentForSave($element, $fieldLayout, $localeId);

		if ($this->saveContent($content))
		{
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
	 * @return ContentModel
	 */
	public function prepElementContentForSave(BaseElementModel $element, FieldLayoutModel $fieldLayout)
	{
		$elementTypeClass = $element->getElementType();
		$elementType = craft()->elements->getElementType($elementTypeClass);

		$content = $element->getContent();

		// Set the required fields from the layout
		$requiredFields = array();

		if ($elementType->hasTitles())
		{
			$requiredFields[] = 'title';
		}

		foreach ($fieldLayout->getFields() as $field)
		{
			if ($field->required)
			{
				$requiredFields[] = $field->fieldId;
			}
		}

		if ($requiredFields)
		{
			$content->setRequiredFields($requiredFields);
		}

		// Give the fieldtypes a chance to clean up the post data
		foreach (craft()->fields->getAllFields() as $field)
		{
			$fieldType = craft()->fields->populateFieldType($field);

			if ($fieldType)
			{
				$fieldType->element = $element;

				$handle = $field->handle;
				$content->$handle = $fieldType->prepValueFromPost($content->$handle);
			}
		}

		return $content;
	}

	/**
	 * Saves a content model to the database.
	 *
	 * @param ContentModel $content
	 * @param bool         $validate Whether to call the model's validate() function first.
	 * @return bool
	 */
	public function saveContent(ContentModel $content, $validate = true)
	{
		if (!$validate || $content->validate())
		{
			$values = array(
				'id'        => $content->id,
				'elementId' => $content->elementId,
				'locale'    => $content->locale,
				'title'     => $content->title,
			);

			$allFields = craft()->fields->getAllFields();

			foreach ($allFields as $field)
			{
				$fieldType = craft()->fields->populateFieldType($field);

				// Only include this value if the content table has a column for it
				if ($fieldType && $fieldType->defineContentAttribute())
				{
					$handle = $field->handle;
					$value = $content->$handle;
					$values[$field->handle] = ModelHelper::packageAttributeValue($value, true);
				}
			}

			$isNewContent = !$content->id;

			if (!$isNewContent)
			{
				$affectedRows = craft()->db->createCommand()
					->update('content', $values, array('id' => $content->id));
			}
			else
			{
				$affectedRows = craft()->db->createCommand()
					->insert('content', $values);

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
		}

		return false;
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
		if (Craft::hasPackage(CraftPackage::Localize))
		{
			// Get the other locales' content
			$rows = craft()->db->createCommand()
				->from('content')
				->where(
					array('and', 'elementId = :elementId', 'locale != :locale'),
					array(':elementId' => $element->id, ':locale' => $content->locale))
				->queryAll();

			$otherContentModels = ContentModel::populateModels($rows);
		}

		$updateOtherContentModels = (Craft::hasPackage(CraftPackage::Localize) && $otherContentModels);

		$fields = craft()->fields->getAllFields();
		$fieldTypes = array();
		$searchKeywordsByLocale = array();

		foreach ($fields as $field)
		{
			$fieldType = craft()->fields->populateFieldType($field);

			if ($fieldType)
			{
				$fieldType->element = $element;

				// Get the field's search keywords
				$handle = $field->handle;
				$fieldSearchKeywords = $fieldType->getSearchKeywords($element->$handle);
				$searchKeywordsByLocale[$content->locale][$field->id] = $fieldSearchKeywords;

				// Should we update this field on the other locales as well?
				if (!$field->translatable && $updateOtherContentModels && $fieldType->defineContentAttribute())
				{
					$handle = $field->handle;

					foreach ($otherContentModels as $otherContentModel)
					{
						// Copy the new field value over to the other locale's content record
						$otherContentModel->$handle = $content->$handle;

						// Queue up the other locale's new keywords too
						$searchKeywordsByLocale[$otherContentModel->locale][$field->id] = $fieldSearchKeywords;
					}
				}

				$fieldTypes[] = $fieldType;
			}
		}

		// Update each of the other content records
		if ($updateOtherContentModels)
		{
			foreach ($otherContentModels as $otherContentModel)
			{
				$this->saveContent($otherContentModel, false);
			}
		}

		// Update the search indexes
		foreach ($searchKeywordsByLocale as $localeId => $keywords)
		{
			craft()->search->indexElementFields($element->id, $localeId, $keywords);
		}

		// Now that everything is finally saved, call fieldtypes' onAfterElementSave();
		foreach ($fieldTypes as $fieldType)
		{
			$fieldType->onAfterElementSave();
		}
	}
}
