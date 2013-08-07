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
	 * @param mixed $attributes
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
	 * @param bool $justIds
	 * @return array
	 */
	public function findElements($criteria = null, $justIds = false)
	{
		$elements = array();
		$subquery = $this->buildElementsQuery($criteria);

		if ($subquery)
		{
			if ($criteria->search)
			{
				$elementIds = $this->_getElementIdsFromQuery($subquery);
				$scoredSearchResults = ($criteria->order == 'score');
				$filteredElementIds = craft()->search->filterElementIdsByQuery($elementIds, $criteria->search, $scoredSearchResults);
				$subquery->andWhere(array('in', 'elements.id', $filteredElementIds));
			}

			$query = craft()->db->createCommand();

			if ($justIds)
			{
				$query->select('r.id');
			}
			else
			{
				// Tests are showing that listing out all of the columns here is actually slower
				// than just doing SELECT * -- probably due to the large number of columns we need to select.
				$query->select('*');
			}

			$query->from('('.$subquery->getText().') AS '.craft()->db->quoteTableName('r'))
			      ->group('r.id');

			$query->params = $subquery->params;

			if ($criteria->order && $criteria->order != 'score')
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

			if ($criteria->search && $scoredSearchResults)
			{
				$searchPositions = array();

				foreach ($result as $row)
				{
					$searchPositions[] = array_search($row['id'], $filteredElementIds);
				}

				array_multisort($searchPositions, $result);
			}

			if ($justIds)
			{
				foreach ($result as $row)
				{
					$elements[] = $row['id'];
				}
			}
			else
			{
				$elementType = $criteria->getElementType();
				$indexBy = $criteria->indexBy;

				foreach ($result as $row)
				{
					// The locale column might be null since the element_i18n table was left-joined into the query,
					// In that case it should be removed from the $row array so that the default value can be used.
					if (!$row['locale'])
					{
						unset($row['locale']);
					}

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
		}

		return $elements;
	}

	/**
	 * Returns the total number of elements that match a given criteria.
	 *
	 * @param mixed $criteria
	 * @return int
	 */
	public function getTotalElements($criteria = null)
	{
		$subquery = $this->buildElementsQuery($criteria);

		if ($subquery)
		{
			$elementIds = $this->_getElementIdsFromQuery($subquery);

			if ($criteria->search)
			{
				$elementIds = craft()->search->filterElementIdsByQuery($elementIds, $criteria->search, false);
			}

			return count($elementIds);
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
			$criteria = $this->getCriteria('Entry', $criteria);
		}

		$elementType = $criteria->getElementType();

		if ($criteria->source)
		{
			$sources = $elementType->getSources();
			$sourceCriteria = $this->_getSourceCriteria($sources, $criteria->source);

			if ($sourceCriteria !== null)
			{
				$criteria->setAttributes($sourceCriteria);
			}
			else
			{
				return false;
			}
		}

		$query = craft()->db->createCommand()
			->select('elements.id, elements.type, elements.enabled, elements.archived, elements.dateCreated, elements.dateUpdated, elements_i18n.locale, elements_i18n.uri')
			->from('elements elements');

		if ($elementType->hasTitles() && $criteria)
		{
			$query->addSelect('content.title');
			$query->join('content content', 'content.elementId = elements.id');
		}

		$query->leftJoin('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id');

		if ($elementType->isTranslatable())
		{
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
				$query->andWhere('elements_i18n.locale = :locale');
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

				$query->andWhere("{$quotedLocaleColumn} IN (".implode(', ', $quotedLocales).')');
				$query->order($localeOrder);
			}
		}

		// The rest
		if ($criteria->id)
		{
			$query->andWhere(DbHelper::parseParam('elements.id', $criteria->id, $query->params));
		}

		if ($criteria->uri !== null)
		{
			$query->andWhere(DbHelper::parseParam('elements_i18n.uri', $criteria->uri, $query->params));
		}

		if ($criteria->archived)
		{
			$query->andWhere('elements.archived = 1');
		}
		else
		{
			$query->andWhere('elements.archived = 0');

			if ($criteria->status)
			{
				$statusConditions = array();
				$statuses = ArrayHelper::stringToArray($criteria->status);

				foreach ($statuses as $status)
				{
					$status = strtolower($status);

					switch ($status)
					{
						case BaseElementModel::ENABLED:
						{
							$statusConditions[] = 'elements.enabled = 1';
							break;
						}

						case BaseElementModel::DISABLED:
						{
							$statusConditions[] = 'elements.enabled = 0';
						}

						default:
						{
							// Maybe the element type supports another status?
							$elementStatusCondition = $elementType->getElementQueryStatusCondition($query, $status);

							if ($elementStatusCondition)
							{
								$statusConditions[] = $elementStatusCondition;
							}
							else if ($elementStatusCondition === false)
							{
								return false;
							}
						}
					}
				}

				if ($statusConditions)
				{
					if (count($statusConditions) == 1)
					{
						$statusCondition = $statusConditions[0];
					}
					else
					{
						array_unshift($statusConditions, 'or');
						$statusCondition = $statusConditions;
					}

					$query->andWhere($statusCondition);
				}
			}
		}

		if ($criteria->dateCreated)
		{
			$query->andWhere(DbHelper::parseDateParam('elements.dateCreated', '=', $criteria->dateCreated, $query->params));
		}

		if ($criteria->dateUpdated)
		{
			$query->andWhere(DbHelper::parseDateParam('elements.dateUpdated', '=', $criteria->dateUpdated, $query->params));
		}

		if ($criteria->parentOf)
		{
			list($childIds, $fieldIds) = $this->_normalizeRelationParams($criteria->parentOf, $criteria->parentField);

			$query->join('relations parents', 'parents.parentId = elements.id');
			$query->andWhere(DbHelper::parseParam('parents.childId', $childIds, $query->params));

			if ($fieldIds)
			{
				$query->andWhere(DbHelper::parseParam('parents.fieldId', $fieldIds, $query->params));
			}
		}

		if ($criteria->childOf)
		{
			list($parentIds, $fieldIds) = $this->_normalizeRelationParams($criteria->childOf, $criteria->childField);

			$query->join('relations children', 'children.childId = elements.id');
			$query->andWhere(DbHelper::parseParam('children.parentId', $parentIds, $query->params));
		}

		if ($elementType->modifyElementsQuery($query, $criteria) !== false)
		{
			return $query;
		}
		else
		{
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
		if (!$elementId)
		{
			return false;
		}

		if (is_array($elementId))
		{
			$condition = array('in', 'id', $elementId);
		}
		else
		{
			$condition = array('id' => $elementId);
		}

		$affectedRows = craft()->db->createCommand()->delete('elements', $condition);

		return (bool) $affectedRows;
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
	 * Returns an element type.
	 *
	 * @param string $class
	 * @return BaseElementType|null
	 */
	public function getElementType($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::Element, $class);
	}

	// Private functions
	// =================

	/**
	 * Returns the criteria for a given source.
	 *
	 * @param array  $sources
	 * @param string $selectedSource
	 * @return array|null
	 */
	private function _getSourceCriteria($sources, $selectedSource)
	{
		if (isset($sources[$selectedSource]))
		{
			if (isset($sources[$selectedSource]['criteria']))
			{
				return $sources[$selectedSource]['criteria'];
			}
			else
			{
				return array();
			}
		}
		else
		{
			// Look through any nested sources
			foreach ($sources as $key => $source)
			{
				if (!empty($source['nested']) && ($nestedSourceCriteria = $this->_getSourceCriteria($source['nested'], $selectedSource)))
				{
					return $nestedSourceCriteria;
				}
			}
		}

		return null;
	}

	/**
	 * Normalizes parentOf and childOf criteria params,
	 * allowing them to be set to ElementCriteriaModel's,
	 * and swapping them with their IDs.
	 *
	 * @param mixed $elements
	 * @param mixed $fields
	 * @return array
	 */
	private function _normalizeRelationParams($elements, $fields)
	{
		$elementIds = array();
		$fieldIds = array();

		// Normalize the element(s)
		$elements = ArrayHelper::stringToArray($elements);

		foreach ($elements as $element)
		{
			if (is_numeric($element) && intval($element) == $element)
			{
				$elementIds[] = $element;
			}
			else if ($element instanceof BaseElementModel)
			{
				$elementIds[] = $element->id;
			}
			else if ($element instanceof ElementCriteriaModel)
			{
				$elementIds = array_merge($elementIds, $element->ids());
			}
		}

		// Normalize the field(s)
		$fields = ArrayHelper::stringToArray($fields);

		foreach ($fields as $field)
		{
			if (is_numeric($field) && intval($field) == $field)
			{
				$fieldIds[] = $field;
			}
			else if (is_string($field))
			{
				$fieldModel = craft()->fields->getFieldByHandle($field);

				if ($fieldModel)
				{
					$fieldIds[] = $fieldModel->id;
				}
			}
		}

		return array($elementIds, $fieldIds);
	}

	/**
	 * Returns the unique element IDs that match a given element query.
	 *
	 * @param DbCommand $query
	 * @return array
	 */
	private function _getElementIdsFromQuery(DbCommand $query)
	{
		// Get the matched element IDs, and then have the SearchService filter them.
		$elementIdsQuery = craft()->db->createCommand()
			->select('elements.id')
			->from('elements elements')
			->group('elements.id');

		$elementIdsQuery->setWhere($query->getWhere());
		$elementIdsQuery->setJoin($query->getJoin());

		$elementIdsQuery->params = $query->params;
		return $elementIdsQuery->queryColumn();
	}
}
