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
			if ($criteria->search)
			{
				$elementIds = $this->_getElementIdsFromQuery($subquery);
				$scoredSearchResults = ($criteria->order == 'score');
				$filteredElementIds = craft()->search->filterElementIdsByQuery($elementIds, $criteria->search, $scoredSearchResults);
				$subquery->andWhere(array('in', 'elements.id', $filteredElementIds));
			}

			$query = craft()->db->createCommand()
				//->select('r.id, r.type, r.expiryDate, r.enabled, r.archived, r.dateCreated, r.dateUpdated, r.locale, r.title, r.uri, r.sectionId, r.slug')
				->select('*')
				->from('('.$subquery->getText().') AS '.craft()->db->quoteTableName('r'))
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

		$query = craft()->db->createCommand()
			->select('elements.id, elements.type, elements.enabled, elements.archived, elements.dateCreated, elements.dateUpdated, elements_i18n.locale, elements_i18n.uri')
			->from('elements elements');

		if ($elementType->isTranslatable())
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
		else
		{
			$query->leftJoin('elements_i18n elements_i18n', 'elements.id = elements_i18n.elementId');
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
