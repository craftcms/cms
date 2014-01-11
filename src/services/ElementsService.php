<?php
namespace Craft;

/**
 *
 */
class ElementsService extends BaseApplicationComponent
{
	private $_joinSourceMatrixBlocksCount;
	private $_joinTargetMatrixBlocksCount;
	private $_joinSourcesCount;
	private $_joinTargetsCount;

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
	 * Returns an element by its ID.
	 *
	 * @param int $elementId
	 * @param string|null $type
	 * @return BaseElementModel|null
	 */
	public function getElementById($elementId, $elementType = null)
	{
		if (!$elementId)
		{
			return null;
		}

		if (!$elementType)
		{
			$elementType = $this->getElementTypeById($elementId);

			if (!$elementType)
			{
				return null;
			}
		}

		$criteria = $this->getCriteria($elementType);
		$criteria->id = $elementId;
		$criteria->status = null;
		return $criteria->first();
	}

	/**
	 * Returns the element type used by the element of a given ID.
	 *
	 * @param int $elementId
	 * @return string|null
	 */
	public function getElementTypeById($elementId)
	{
		return craft()->db->createCommand()
			->select('type')
			->from('elements')
			->where(array('id' => $elementId))
			->queryScalar();
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
		$subquery = $this->buildElementsQuery($criteria, $fieldColumns);

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

				if ($fieldColumns)
				{
					foreach ($orderColumns as $i => $orderColumn)
					{
						// Is this column for a custom field?
						foreach ($fieldColumns as $column)
						{
							if (preg_match('/^'.$column['handle'].'\b(.*)$/', $orderColumn, $matches))
							{
								// Use the field column name instead
								$orderColumns[$i] = $column['column'].$matches[1];
								// Don't break from the loop though because there could be more than one column that uses this handle!
							}
						}
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
							$content = array();
							$content['elementId'] = $row['id'];
							$content['locale'] = $criteria->locale;

							if (isset($row['title']))
							{
								$content['title'] = $row['title'];
								unset($row['title']);
							}

							// Did we actually get the requested locale back?
							if ($row['locale'] == $criteria->locale)
							{
								$content['id'] = $row['contentId'];
							}
							else
							{
								$row['locale'] = $criteria->locale;
							}

							if ($fieldColumns)
							{
								foreach ($fieldColumns as $column)
								{
									if (!empty($row[$column['column']]))
									{
										$content[$column['handle']] = $row[$column['column']];
										unset($row[$column['column']]);
									}
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
							$element->setPrev($lastElement);
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
	 * @param array &$fieldColumns
	 * @return DbCommand|false
	 */
	public function buildElementsQuery(&$criteria = null, &$fieldColumns = array())
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
			$contentTable = $elementType->getContentTableForElementsQuery($criteria);

			if ($contentTable)
			{
				$contentCols = 'content.id AS contentId, content.locale';

				if ($elementType->hasTitles())
				{
					$contentCols .= ', content.title';
				}

				$fieldColumns = $elementType->getContentFieldColumnsForElementsQuery($criteria);

				foreach ($fieldColumns as $column)
				{
					$contentCols .= ', content.'.$column['column'];
				}

				$query->addSelect($contentCols);
				$query->join($contentTable.' content', 'content.elementId = elements.id');
				$this->_orderByRequestedLocale($query, 'content', $criteria->locale);
			}
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

		// Relational params

		// Convert the old childOf and parentOf params to the relatedTo param
		// childOf(element)  => relatedTo({ source: element })
		// parentOf(element) => relatedTo({ target: element })
		if (!$criteria->relatedTo && ($criteria->childOf || $criteria->parentOf))
		{
			if ($criteria->childOf && $criteria->parentOf)
			{
				$criteria->relatedTo = array('and',
					array('sourceElement' => $criteria->childOf, 'field' => $criteria->childField),
					array('targetElement' => $criteria->parentOf, 'field' => $criteria->parentField)
				);
			}
			else if ($criteria->childOf)
			{
				$criteria->relatedTo = array('sourceElement' => $criteria->childOf, 'field' => $criteria->childField);
			}
			else
			{
				$criteria->relatedTo = array('targetElement' => $criteria->parentOf, 'field' => $criteria->parentField);
			}
		}

		if ($criteria->relatedTo)
		{
			$this->_joinSourceMatrixBlocksCount = 0;
			$this->_joinTargetMatrixBlocksCount = 0;
			$this->_joinSourcesCount = 0;
			$this->_joinTargetsCount = 0;

			$relConditions = $this->_parseRelationParam($criteria->relatedTo, $query);

			if ($relConditions === false)
			{
				return false;
			}

			$query->andWhere($relConditions);

			// If there's only one relation criteria and it's specifically for grabbing target elements,
			// allow the query to order by the relation sort order
			if ($this->_joinSourcesCount == 1 && !$this->_joinTargetsCount && !$this->_joinSourceMatrixBlocksCount && !$this->_joinTargetMatrixBlocksCount)
			{
				$query->addSelect('sources1.sortOrder');
			}
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
	 * Parses a relatedTo criteria param and returns the condition(s) or 'false' if there's an issue.
	 *
	 * @access private
	 * @param mixed $relatedTo
	 * @param DbCommand $query
	 * @return mixed
	 */
	private function _parseRelationParam($relatedTo, DbCommand $query)
	{
		// Ensure the criteria is an array
		$relatedTo = ArrayHelper::stringToArray($relatedTo);

		if (isset($relatedTo['element']) || isset($relatedTo['sourceElement']) || isset($relatedTo['targetElement']))
		{
			$relatedTo = array($relatedTo);
		}

		$conditions = array();

		if ($relatedTo[0] == 'and' || $relatedTo[0] == 'or')
		{
			$glue = array_shift($relatedTo);
		}
		else
		{
			$glue = 'or';
		}

		foreach ($relatedTo as $relCriteria)
		{
			$condition = $this->_subparseRelationParam($relCriteria, $query);

			if ($condition)
			{
				$conditions[] = $condition;
			}
			else if ($glue == 'or')
			{
				continue;
			}
			else
			{
				return false;
			}
		}

		if ($conditions)
		{
			if (count($conditions) == 1)
			{
				return $conditions[0];
			}
			else
			{
				array_unshift($conditions, $glue);
				return $conditions;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Parses a part of a relatedTo criteria param and returns the condition or 'false' if there's an issue.
	 *
	 * @access private
	 * @param mixed $relCriteria
	 * @param DbCommand $query
	 * @return mixed
	 */
	private function _subparseRelationParam($relCriteria, DbCommand $query)
	{
		if (!is_array($relCriteria))
		{
			$relCriteria = array('element' => $relCriteria);
		}

		// Get the element IDs, wherever they are
		$relElementIds = array();

		foreach (array('element', 'sourceElement', 'targetElement') as $elementParam)
		{
			if (isset($relCriteria[$elementParam]))
			{
				$elements = ArrayHelper::stringToArray($relCriteria[$elementParam]);

				foreach ($elements as $element)
				{
					if (is_numeric($element))
					{
						$relElementIds[] = $element;
					}
					else if ($element instanceof BaseElementModel)
					{
						$relElementIds[] = $element->id;
					}
					else if ($element instanceof ElementCriteriaModel)
					{
						$relElementIds = array_merge($relElementIds, $element->ids());
					}
				}

				break;
			}
		}

		if (!$relElementIds)
		{
			return false;
		}

		// Going both ways?
		if (isset($relCriteria['element']))
		{
			if (!isset($relCriteria['field']))
			{
				$relCriteria['field'] = null;
			}

			return $this->_parseRelationParam(array('or',
				array('sourceElement' => $relElementIds, 'field' => $relCriteria['field']),
				array('targetElement' => $relElementIds, 'field' => $relCriteria['field'])
			), $query);
		}

		$conditions = array();
		$normalFieldIds = array();

		if (!empty($relCriteria['field']))
		{
			// Loop through all of the fields in this rel critelia,
			// create the Matrix-specific conditions right away
			// and save the normal field IDs for later
			$fields = ArrayHelper::stringToArray($relCriteria['field']);

			foreach ($fields as $field)
			{
				$fieldModel = null;

				if (is_numeric($field))
				{
					$fieldHandleParts = null;
					$fieldModel = craft()->fields->getFieldById($field);
				}
				else
				{
					$fieldHandleParts = explode('.', $field);
					$fieldModel = craft()->fields->getFieldByHandle($fieldHandleParts[0]);
				}

				if (!$fieldModel)
				{
					continue;
				}

				// Is this a Matrix field?
				if ($fieldModel->type == 'Matrix')
				{
					$blockTypeFieldIds = array();

					// Searching by a specific block type field?
					if (isset($fieldHandleParts[1]))
					{
						// There could be more than one block type field with this handle,
						// so we must loop through all of the block types on this Matrix field
						$blockTypes = craft()->matrix->getBlockTypesByFieldId($fieldModel->id);

						foreach ($blockTypes as $blockType)
						{
							foreach ($blockType->getFields() as $blockTypeField)
							{
								if ($blockTypeField->handle == $fieldHandleParts[1])
								{
									$blockTypeFieldIds[] = $blockTypeField->id;
									break;
								}
							}
						}

						if (!$blockTypeFieldIds)
						{
							continue;
						}
					}

					if (isset($relCriteria['sourceElement']))
					{
						$this->_joinSourcesCount++;
						$this->_joinTargetMatrixBlocksCount++;

						$sourcesAlias            = 'sources'.$this->_joinSourcesCount;
						$targetMatrixBlocksAlias = 'target_matrixblocks'.$this->_joinTargetMatrixBlocksCount;

						$query->leftJoin('relations '.$sourcesAlias, $sourcesAlias.'.targetId = elements.id');
						$query->leftJoin('matrixblocks '.$targetMatrixBlocksAlias, $targetMatrixBlocksAlias.'.id = '.$sourcesAlias.'.sourceId');

						$condition = array('and',
							DbHelper::parseParam($targetMatrixBlocksAlias.'.ownerId', $relElementIds, $query->params),
							$targetMatrixBlocksAlias.'.fieldId = '.$fieldModel->id
						);

						if ($blockTypeFieldIds)
						{
							$condition[] = DbHelper::parseParam($sourcesAlias.'.fieldId', $blockTypeFieldIds, $query->params);
						}
					}
					else
					{
						$this->_joinSourceMatrixBlocksCount++;
						$sourceMatrixBlocksAlias = 'source_matrixblocks'.$this->_joinSourceMatrixBlocksCount;
						$matrixBlockTargetsAlias = 'matrixblock_targets'.$this->_joinSourceMatrixBlocksCount;

						$query->leftJoin('matrixblocks '.$sourceMatrixBlocksAlias, $sourceMatrixBlocksAlias.'.ownerId = elements.id');
						$query->leftJoin('relations '.$matrixBlockTargetsAlias, $matrixBlockTargetsAlias.'.sourceId = '.$sourceMatrixBlocksAlias.'.id');

						$condition = array('and',
							DbHelper::parseParam($matrixBlockTargetsAlias.'.targetId', $relElementIds, $query->params),
							$sourceMatrixBlocksAlias.'.fieldId = '.$fieldModel->id
						);

						if ($blockTypeFieldIds)
						{
							$condition[] = DbHelper::parseParam($matrixBlockTargetsAlias.'.fieldId', $blockTypeFieldIds, $query->params);
						}
					}

					$conditions[] = $condition;
				}
				else
				{
					$normalFieldIds[] = $fieldModel->id;
				}
			}
		}

		// If there were no fields, or there are some non-Matrix fields, add the normal relation condition
		// (Basically, run this code if the rel criteria wasn't exclusively for Matrix.)
		if (empty($relCriteria['field']) || $normalFieldIds)
		{
			if (isset($relCriteria['sourceElement']))
			{
				$this->_joinSourcesCount++;
				$relTableAlias = 'sources'.$this->_joinSourcesCount;
				$relConditionColumn = 'sourceId';
				$relElementColumn = 'targetId';
			}
			else if (isset($relCriteria['targetElement']))
			{
				$this->_joinTargetsCount++;
				$relTableAlias = 'targets'.$this->_joinTargetsCount;
				$relConditionColumn = 'targetId';
				$relElementColumn = 'sourceId';
			}

			$query->leftJoin('relations '.$relTableAlias, $relTableAlias.'.'.$relElementColumn.' = elements.id');
			$condition = DbHelper::parseParam($relTableAlias.'.'.$relConditionColumn, $relElementIds, $query->params);

			if ($normalFieldIds)
			{
				$condition = array('and', $condition, DbHelper::parseParam($relTableAlias.'.fieldId', $normalFieldIds, $query->params));
			}

			$conditions[] = $condition;
		}

		if ($conditions)
		{
			if (count($conditions) == 1)
			{
				return $conditions[0];
			}
			else
			{
				array_unshift($conditions, 'or');
				return $conditions;
			}
		}
		else
		{
			return false;
		}
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
