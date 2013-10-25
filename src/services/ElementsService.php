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

			// Get a list of all the field handles we might be dealing with
			$fieldHandles = array();

			foreach (craft()->fields->getFieldsWithContent() as $field)
			{
				$fieldHandles[] = $field->handle;
			}

			if ($criteria->fixedOrder)
			{
				$ids = ArrayHelper::stringToArray($criteria->id);

				if (!$ids)
				{
					return array();
				}

				$query->order(craft()->db->getSchema()->orderByColumnValues('id', $ids));
			}
			else if ($criteria->order && $criteria->order != 'score')
			{
				$orderColumns = ArrayHelper::stringToArray($criteria->order);

				if ($fieldHandles)
				{
					// Add the "field_" prefix to any custom fields we're ordering by
					$fieldColumnRegex = '/^(?:'.implode('|', $fieldHandles).')\b/';
					foreach ($orderColumns as $i => $orderColumn)
					{
						$orderColumns[$i] = preg_replace($fieldColumnRegex, 'field_$0', $orderColumn);
					}
				}

				$query->order(implode(', ', $orderColumns));
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

			if ($result)
			{
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
					$lastElement = null;

					foreach ($result as $row)
					{
						if ($elementType->hasContent())
						{
							// Separate the content values from the main element attributes
							$content = new ContentModel();
							$content->elementId = $row['id'];
							$content->locale = $criteria->locale;
							$content->title = $row['title'];
							unset($row['title']);

							// Did we actually get the requested locale back?
							if ($row['locale'] == $criteria->locale)
							{
								$content->id = $row['contentId'];
							}
							else
							{
								$row['locale'] = $criteria->locale;
							}

							foreach ($fieldHandles as $fieldHandle)
							{
								if (isset($row['field_'.$fieldHandle]))
								{
									$content->$fieldHandle = $row['field_'.$fieldHandle];
									unset($row['field_'.$fieldHandle]);
								}
							}
						}

						$element = $elementType->populateElementModel($row);

						if ($elementType->hasContent())
						{
							$element->setContent($content);
						}

						if ($indexBy)
						{
							$elements[$element->$indexBy] = $element;
						}
						else
						{
							$elements[] = $element;
						}

						if ($lastElement)
						{
							$lastElement->setNext($element);
							$element->setPrev($element);
						}
						else
						{
							$element->setPrev(false);
						}

						$lastElement = $element;
					}

					$lastElement->setNext(false);
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

		if (!$criteria->locale)
		{
			// Default to the current app target locale
			$criteria->locale = craft()->language;
		}

		$elementType = $criteria->getElementType();

		$query = craft()->db->createCommand()
			->select('elements.id, elements.type, elements.enabled, elements.archived, elements.dateCreated, elements.dateUpdated')
			->from('elements elements');

		if ($elementType->hasContent())
		{
			$contentCols = 'content.id AS contentId, content.locale, content.title';

			foreach (craft()->fields->getFieldsWithContent() as $field)
			{
				$contentCols .= ', content.field_'.$field->handle;
			}

			$query->addSelect($contentCols);
			$query->join(craft()->content->contentTable.' content', 'content.elementId = elements.id');
			$this->_orderByRequestedLocale($query, 'content', $criteria->locale);
		}

		if ($elementType->isLocalized())
		{
			$query->addSelect('elements_i18n.uri');
			$query->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id');

			if (!$elementType->hasContent())
			{
				$query->addSelect('elements_i18n.locale');
				$this->_orderByRequestedLocale($query, 'elements_i18n', $criteria->locale);
			}
			else
			{
				$query->andWhere('elements_i18n.locale = content.locale');
			}

			if ($criteria->uri !== null)
			{
				$query->andWhere(DbHelper::parseParam('elements_i18n.uri', $criteria->uri, $query->params));
			}
		}

		if ($criteria->id === false)
		{
			return false;
		}

		if ($criteria->id)
		{
			$query->andWhere(DbHelper::parseParam('elements.id', $criteria->id, $query->params));
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
					$status = mb_strtolower($status);

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
			$query->andWhere(DbHelper::parseDateParam('elements.dateCreated', $criteria->dateCreated, $query->params));
		}

		if ($criteria->dateUpdated)
		{
			$query->andWhere(DbHelper::parseDateParam('elements.dateUpdated', $criteria->dateUpdated, $query->params));
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

			if ($fieldIds)
			{
				$query->andWhere(DbHelper::parseParam('children.fieldId', $fieldIds, $query->params));
			}

			// Make it possible to order by the relation sort order
			$query->addSelect('children.sortOrder');
		}

		// Field conditions
		foreach ($criteria->getSupportedFieldHandles() as $fieldHandle)
		{
			if ($criteria->$fieldHandle !== false)
			{
				$field = craft()->fields->getFieldByHandle($fieldHandle);
				$fieldType = $field->getFieldType();

				if ($fieldType->modifyElementsQuery($query, $criteria->$fieldHandle) === false)
				{
					return false;
				}
			}
		}

		// Give the element type a chance to make changes
		if ($elementType->modifyElementsQuery($query, $criteria) === false)
		{
			return false;
		}

		return $query;
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

	// Misc
	// ====

	/**
	 * Parses a string for element reference tags.
	 *
	 * @param string $str
	 * @return string|array
	 */
	public function parseRefs($str)
	{
		if (strpos($str, '{') !== false)
		{
			global $refTagsByElementType;
			$refTagsByElementType = array();

			$str = preg_replace_callback('/\{(\w+)\:([^\:\}]+)(?:\:([^\:\}]+))?\}/', function($matches)
			{
				global $refTagsByElementType;

				$elementTypeHandle = ucfirst($matches[1]);
				$token = '{'.StringHelper::randomString(9).'}';

				$refTagsByElementType[$elementTypeHandle][] = array('token' => $token, 'matches' => $matches);

				return $token;
			}, $str);

			if ($refTagsByElementType)
			{
				$search = array();
				$replace = array();

				$things = array('id', 'ref');

				foreach ($refTagsByElementType as $elementTypeHandle => $refTags)
				{
					$elementType = craft()->elements->getElementType($elementTypeHandle);

					if (!$elementType)
					{
						// Just put the ref tags back the way they were
						foreach ($refTags as $refTag)
						{
							$search[] = $refTag['token'];
							$replace[] = $refTag['matches'][0];
						}
					}
					else
					{
						$refTagsById = array();
						$refTagsByRef = array();

						foreach ($refTags as $refTag)
						{
							// Searching by ID?
							if (is_numeric($refTag['matches'][2]))
							{
								$refTagsById[$refTag['matches'][2]][] = $refTag;
							}
							else
							{
								$refTagsByRef[$refTag['matches'][2]][] = $refTag;
							}
						}

						// Things are about to get silly...
						foreach ($things as $thing)
						{
							$refTagsByThing = ${'refTagsBy'.ucfirst($thing)};

							if ($refTagsByThing)
							{
								$criteria = craft()->elements->getCriteria($elementTypeHandle);
								$criteria->$thing = array_keys($refTagsByThing);
								$elements = $criteria->find();

								$elementsByThing = array();

								foreach ($elements as $element)
								{
									$elementsByThing[$element->$thing] = $element;
								}

								foreach ($refTagsByThing as $thingVal => $refTags)
								{
									if (isset($elementsByThing[$thingVal]))
									{
										$element = $elementsByThing[$thingVal];
									}
									else
									{
										$element = false;
									}

									foreach($refTags as $refTag)
									{
										$search[] = $refTag['token'];

										if ($element)
										{
											if (!empty($refTag['matches'][3]) && isset($element->{$refTag['matches'][3]}))
											{
												$value = (string) $element->{$refTag['matches'][3]};
												$replace[] = $this->parseRefs($value);
											}
											else
											{
												// Default to the URL
												$replace[] = $element->getUrl();
											}
										}
										else
										{
											$replace[] = $refTag['matches'][0];
										}
									}
								}
							}
						}
					}
				}

				$str = str_replace($search, $replace, $str);
			}

			unset ($refTagsByElementType);
		}

		return $str;
	}

	// Private functions
	// =================

	/**
	 * Adds ORDER BY's to the passed-in query to sort the requested locale up to the top.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param string $table
	 * @param string|null $localeId
	 */
	private function _orderByRequestedLocale(DbCommand $query, $table, $localeId)
	{
		$localeIds = craft()->i18n->getSiteLocaleIds();

		if (count($localeIds) > 1)
		{
			// Move the requested locale to the first position
			array_unshift($localeIds, $localeId);
			$localeIds = array_unique($localeIds);

			// Order the results by locale
			$query->order(craft()->db->getSchema()->orderByColumnValues($table.'.locale', $localeIds));
		}
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
