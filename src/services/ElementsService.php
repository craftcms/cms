<?php
namespace Craft;

/**
 *
 */
class ElementsService extends BaseApplicationComponent
{
	// Finding Elements
	// ================

	/**
	 * Returns an element criteria model for a given element type.
	 *
	 * @param string $type
	 * @return ElementCriteriaModel
	 * @throws Exception
	 */
	public function getCriteria($type, $attributes = null)
	{
		$elementType = $this->getElementType($type);

		if (!$elementType)
		{
			throw new Exception(Craft::t('No element type exists by the type “{type}”.', array('type' => $type)));
		}

		return new ElementCriteriaModel($attributes, $elementType);
	}

	/**
	 * Finds elements.
	 *
	 * @param mixed $criteria
	 * @return array
	 */
	public function findElements($criteria = null)
	{
		$elements = array();
		$subquery = $this->buildElementsQuery($criteria);

		if ($subquery)
		{
			$query = craft()->db->createCommand()
				//->select('r.id, r.type, r.expiryDate, r.enabled, r.archived, r.dateCreated, r.dateUpdated, r.locale, r.title, r.uri, r.sectionId, r.slug')
				->select('*')
				->from('('.$subquery->getText().') AS '.craft()->db->quoteTableName('r'))
				->group('r.id');

			$query->params = $subquery->params;

			if ($criteria->order)
			{
				$query->order($criteria->order);
			}

			if ($criteria->offset)
			{
				$query->offset($criteria->offset);
			}

			if ($criteria->limit)
			{
				$query->limit($criteria->limit);
			}

			$result = $query->queryAll();

			$elementType = $criteria->getElementType();
			$indexBy = $criteria->indexBy;

			foreach ($result as $row)
			{
				$element = $elementType->populateElementModel($row);

				if ($indexBy)
				{
					$elements[$element->$indexBy] = $element;
				}
				else
				{
					$elements[] = $element;
				}
			}
		}

		return $elements;
	}

	/**
	 * Finds an element.
	 *
	 * @param mixed $criteria
	 * @return SectionElementModel|null
	 */
	public function findElement($criteria = null)
	{
		$query = $this->buildElementsQuery($criteria);

		if ($query)
		{
			$result = $query->queryRow();

			if ($result)
			{
				return $criteria->getElementType()->populateElementModel($result);
			}
		}
	}

	/**
	 * Gets the total number of elements.
	 *
	 * @param mixed $criteria
	 * @return int
	 */
	public function getTotalElements($criteria = null)
	{
		$subquery = $this->buildElementsQuery($criteria);

		if ($subquery)
		{
			$subquery->select('elements.id')->group('elements.id');

			$query = craft()->db->createCommand()
				->from('('.$subquery->getText().') AS '.craft()->db->quoteTableName('r'));

			$query->params = $subquery->params;

			return $query->count('r.id');
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns a DbCommand instance ready to search for elements based on a given element criteria.
	 *
	 * @param mixed &$criteria
	 * @return DbCommand|false
	 */
	public function buildElementsQuery(&$criteria = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = $this->getCriteria('SectionElement', $criteria);
		}

		$elementType = $criteria->getElementType();

		$query = craft()->db->createCommand()
			->select('elements.id, elements.type, elements.enabled, elements.archived, elements.dateCreated, elements.dateUpdated, elements_i18n.locale, elements_i18n.uri')
			->from('elements elements');

		$whereConditions = array();

		if ($elementType->isLocalizable())
		{
			$query->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id');

			// Locale conditions
			if (!$criteria->locale)
			{
				$criteria->locale = craft()->language;
			}

			$localeIds = array_unique(array_merge(
				array($criteria->locale),
				craft()->i18n->getSiteLocaleIds()
			));

			$quotedLocaleColumn = craft()->db->quoteColumnName('elements_i18n.locale');

			if (count($localeIds) == 1)
			{
				$whereConditions[] = 'elements_i18n.locale = :locale';
				$query->params[':locale'] = $localeIds[0];
			}
			else
			{
				$quotedLocales = array();
				$localeOrder = array();

				foreach ($localeIds as $localeId)
				{
					$quotedLocale = craft()->db->quoteValue($localeId);
					$quotedLocales[] = $quotedLocale;
					$localeOrder[] = "({$quotedLocaleColumn} = {$quotedLocale}) DESC";
				}

				$whereConditions[] = "{$quotedLocaleColumn} IN (".implode(', ', $quotedLocales).')';
				$query->order($localeOrder);
			}
		}
		else
		{
			$query->leftJoin('elements_i18n elements_i18n', 'elements.id = elements_i18n.elementId');
		}

		// The rest
		if ($criteria->id)
		{
			$whereConditions[] = DbHelper::parseParam('elements.id', $criteria->id, $query->params);
		}

		if ($criteria->uri)
		{
			$whereConditions[] = DbHelper::parseParam('elements_i18n.uri', $criteria->uri, $query->params);
		}

		if ($criteria->archived)
		{
			$whereConditions[] = 'elements.archived = 1';
		}
		else
		{
			$whereConditions[] = 'elements.archived = 0';
		}

		// Apply the conditions
		if (count($whereConditions) == 1)
		{
			$query->where($whereConditions[0]);
		}
		else
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions);
		}

		// Give the element type a chance to make any changes
		$elementType = $criteria->getElementType();

		if ($elementType->modifyElementsQuery($query, $criteria) !== false)
		{
			return $query;
		}
		else
		{
			return false;
		}
	}

	// Saving Element Content
	// ======================

	/**
	 * Returns the content record for a given element and locale.
	 *
	 * @param int $elementId
	 * @param string|null $localeId
	 * @return ContentRecord|null
	 */
	public function getContentRecord($elementId, $localeId = null)
	{
		$attributes = array('elementId' => $elementId);

		if ($localeId)
		{
			$attributes['locale'] = $localeId;
		}

		return ContentRecord::model()->findByAttributes($attributes);
	}

	/**
	 * Returns the content for a given element and locale.
	 *
	 * @param int $elementId
	 * @param string|null $localeId
	 * @return array|null
	 */
	public function getElementContent($elementId, $localeId = null)
	{
		$record = $this->getContentRecord($elementId, $localeId);

		if ($record)
		{
			return $record->getAttributes();
		}
	}

	/**
	 * Preps an ContentRecord to be saved with an element's data.
	 *
	 * @param ElementModel $element
	 * @param FieldLayoutModel $fieldLayout
	 * @param stirng|null $localeId
	 * @return ContentRecord
	 */
	public function prepElementContent(ElementModel $element, FieldLayoutModel $fieldLayout, $localeId = null)
	{
		if ($element->id)
		{
			$contentRecord = $this->getContentRecord($element->id, $localeId);
		}

		if (empty($contentRecord))
		{
			$contentRecord = new ContentRecord();
			$contentRecord->elementId = $element->id;

			if ($localeId)
			{
				$contentRecord->locale = $localeId;
			}
			else
			{
				$contentRecord->locale = craft()->i18n->getPrimarySiteLocale()->getId();
			}
		}

		// Set the required fields from the layout
		$requiredFields = array();

		foreach ($fieldLayout->getFields() as $field)
		{
			if ($field->required)
			{
				$requiredFields[] = $field->fieldId;
			}
		}

		if ($requiredFields)
		{
			$contentRecord->setRequiredFields($requiredFields);
		}

		// Populate the fields' content
		foreach (craft()->fields->getAllFields() as $field)
		{
			$fieldType = craft()->fields->populateFieldType($field);
			$fieldType->element = $element;

			if ($fieldType->defineContentAttribute())
			{
				$handle = $field->handle;
				$contentRecord->$handle = $fieldType->getPostData();
			}
		}

		return $contentRecord;
	}

	/**
	 * Performs post-save element operations, such as calling all fieldtypes' onAfterElementSave() methods.
	 *
	 * @param ElementModel $element
	 * @param ContentRecord $element
	 */
	public function postSaveOperations(ElementModel $element, ContentRecord $contentRecord)
	{
		if (Craft::hasPackage(CraftPackage::Language))
		{
			// Get the other locales' content records
			$otherContentRecords = ContentRecord::model()->findAll(
				'elementId = :elementId AND locale != :locale',
				array(':elementId' => $element->id, ':locale' => $contentRecord->locale)
			);
		}

		$updateOtherContentRecords = (Craft::hasPackage(CraftPackage::Language) && $otherContentRecords);

		$fields = craft()->fields->getAllFields();
		$fieldTypes = array();

		foreach ($fields as $field)
		{
			$fieldType = craft()->fields->populateFieldType($field);
			$fieldType->element = $element;
			$fieldTypes[] = $fieldType;

			// If this field isn't translatable, we should set its new value on the other content records
			if (!$field->translatable && $updateOtherContentRecords && $fieldType->defineContentAttribute())
			{
				$handle = $field->handle;

				foreach ($otherContentRecords as $otherContentRecord)
				{
					$otherContentRecord->$handle = $contentRecord->$handle;
				}
			}
		}

		// Update each of the other content records
		if ($updateOtherContentRecords)
		{
			foreach ($otherContentRecords as $otherContentRecord)
			{
				$otherContentRecord->save();
			}
		}

		// Now that everything is finally saved, call fieldtypes' onAfterElementSave();
		foreach ($fieldTypes as $fieldType)
		{
			$fieldType->onAfterElementSave();
		}
	}

	/**
	 * Saves an element's content.
	 *
	 * @param ElementModel $element
	 * @param FieldLayoutModel $fieldLayout
	 * @param stirng|null $localeId
	 */
	public function saveElementContent(ElementModel $element, FieldLayoutModel $fieldLayout, $localeId = null)
	{
		if (!$element->id)
		{
			throw new Exception(Craft::t('Cannot save the content of an unsaved element.'));
		}

		$contentRecord = $this->prepElementContent($element, $fieldLayout, $localeId);

		if ($contentRecord->save())
		{
			$this->postSaveOperations($element, $contentRecord);
			return true;
		}
		else
		{
			$element->addErrors($contentRecord->getErrors());
			return false;
		}
	}

	// Element helper functions
	// ========================

	/**
	 * Returns an element's URI for a given locale.
	 *
	 * @param int $elementId
	 * @param string $localeId
	 * @return string
	 */
	public function getElementUriForLocale($elementId, $localeId)
	{
		return craft()->db->createCommand()
			->select('uri')
			->from('elements_i18n')
			->where(array('elementId' => $elementId, 'locale' => $localeId))
			->queryScalar();
	}

	/**
	 * Returns the CP edit URL for a given element.
	 *
	 * @param ElementModel $element
	 * @return string|null
	 */
	public function getCpEditUrlForElement(ElementModel $element)
	{
		$elementType = $this->getElementType($element->type);

		if ($elementType)
		{
			$uri = $elementType->getCpEditUriForElement($element);

			if ($uri !== false)
			{
				return UrlHelper::getCpUrl($uri);
			}
		}
	}

	/**
	 * Returns the localization record for a given element and locale.
	 *
	 * @param int $elementId
	 * @param string $locale
	 */
	public function getElementLocaleRecord($elementId, $localeId)
	{
		return ElementLocaleRecord::model()->findByAttributes(array(
			'elementId' => $elementId,
			'locale'  => $localeId
		));
	}

	/**
	 * Deletes an element(s) by its ID(s).
	 *
	 * @param int|array $elementId
	 * @return bool
	 */
	public function deleteElementById($elementId)
	{
		if (is_array($elementId))
		{
			$condition = array('in', 'id', $elementId);
		}
		else
		{
			$condition = array('id' => $elementId);
		}

		craft()->db->createCommand()->delete('elements', $condition);

		return true;
	}

	// Element types
	// =============

	/**
	 * Returns all installed element types.
	 *
	 * @return array
	 */
	public function getAllElementTypes()
	{
		return craft()->components->getComponentsByType(ComponentType::Element);
	}

	/**
	 * Gets an element type.
	 *
	 * @param string $class
	 * @return BaseElementType|null
	 */
	public function getElementType($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::Element, $class);
	}
}
