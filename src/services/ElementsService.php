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
		$criteria->localeEnabled = null;
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
		$query = $this->buildElementsQuery($criteria, $contentTable, $fieldColumns);

		if ($query)
		{
			if ($criteria->search)
			{
				$elementIds = $this->_getElementIdsFromQuery($query);
				$scoredSearchResults = ($criteria->order == 'score');
				$filteredElementIds = craft()->search->filterElementIdsByQuery($elementIds, $criteria->search, $scoredSearchResults);
				$query->andWhere(array('in', 'elements.id', $filteredElementIds));
			}

			if ($justIds)
			{
				$query->select('elements.id');
			}

			if ($criteria->fixedOrder)
			{
				$ids = ArrayHelper::stringToArray($criteria->id);

				if (!$ids)
				{
					return array();
				}

				$query->order(craft()->db->getSchema()->orderByColumnValues('elements.id', $ids));
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
						if ($contentTable)
						{
							// Separate the content values from the main element attributes
							$content = array(
								'id'        => (isset($row['contentId']) ? $row['contentId'] : null),
								'elementId' => $row['id'],
								'locale'    => $criteria->locale,
								'title'     => (isset($row['title']) ? $row['title'] : null)
							);

							unset($row['title']);

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

						$row['locale'] = $criteria->locale;
						$element = $elementType->populateElementModel($row);

						if ($contentTable)
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
		$query = $this->buildElementsQuery($criteria);

		if ($query)
		{
			$elementIds = $this->_getElementIdsFromQuery($query);

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
	 * @param null  &$contentTable
	 * @param null  &$fieldColumns
	 * @return DbCommand|false
	 */
	public function buildElementsQuery(&$criteria = null, &$contentTable = null, &$fieldColumns = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = $this->getCriteria('Entry', $criteria);
		}

		$elementType = $criteria->getElementType();

		if (!$criteria->locale)
		{
			if ($elementType->isLocalized())
			{
				// Default to the current app target locale
				$criteria->locale = craft()->language;
			}
			else
			{
				// Default to the primary site locale
				$criteria->locale = craft()->i18n->getPrimarySiteLocaleId();
			}
		}

		// Set up the query

		$query = craft()->db->createCommand()
			->select('elements.id, elements.type, elements.enabled, elements.archived, elements.dateCreated, elements.dateUpdated, elements_i18n.slug, elements_i18n.uri, elements_i18n.enabled AS localeEnabled')
			->from('elements elements')
			->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id')
			->where('elements_i18n.locale = :locale', array(':locale' => $criteria->locale))
			->group('elements.id');

		if ($elementType->hasContent())
		{
			$contentTable = $elementType->getContentTableForElementsQuery($criteria);

			if ($contentTable)
			{
				$contentCols = 'content.id AS contentId';

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
				$query->andWhere('content.locale = :locale');
			}
		}

		// Basic element params

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

		// i18n params

		if ($criteria->slug)
		{
			$query->andWhere(DbHelper::parseParam('elements_i18n.slug', $criteria->slug, $query->params));
		}

		if ($criteria->uri)
		{
			$query->andWhere(DbHelper::parseParam('elements_i18n.uri', $criteria->uri, $query->params));
		}

		if ($criteria->localeEnabled)
		{
			$query->andWhere('elements_i18n.enabled = 1');
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

		// Field params

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

		// Structure params

		if ($query->isJoined('structureelements'))
		{
			$query->addSelect('structureelements.root, structureelements.lft, structureelements.rgt, structureelements.level');

			if ($criteria->ancestorOf)
			{
				if (!$criteria->ancestorOf instanceof BaseElementModel)
				{
					$criteria->ancestorOf = craft()->elements->getElementById($criteria->ancestorOf, $elementType->getClassHandle());
				}

				if ($criteria->ancestorOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.lft < :ancestorOf_lft',
							'structureelements.rgt > :ancestorOf_rgt',
							'structureelements.root = :ancestorOf_root'
						),
						array(
							':ancestorOf_lft'  => $criteria->ancestorOf->lft,
							':ancestorOf_rgt'  => $criteria->ancestorOf->rgt,
							':ancestorOf_root' => $criteria->ancestorOf->root
						)
					);

					if ($criteria->ancestorDist)
					{
						$query->andWhere('structureelements.level >= :ancestorOf_level',
							array(':ancestorOf_level' => $criteria->ancestorOf->level - $criteria->ancestorDist)
						);
					}
				}
			}

			if ($criteria->descendantOf)
			{
				if (!$criteria->descendantOf instanceof BaseElementModel)
				{
					$criteria->descendantOf = craft()->elements->getElementById($criteria->descendantOf, $elementType->getClassHandle());
				}

				if ($criteria->descendantOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.lft > :descendantOf_lft',
							'structureelements.rgt < :descendantOf_rgt',
							'structureelements.root = :descendantOf_root'
						),
						array(
							':descendantOf_lft'  => $criteria->descendantOf->lft,
							':descendantOf_rgt'  => $criteria->descendantOf->rgt,
							':descendantOf_root' => $criteria->descendantOf->root
						)
					);

					if ($criteria->descendantDist)
					{
						$query->andWhere('structureelements.level <= :descendantOf_level',
							array(':descendantOf_level' => $criteria->descendantOf->level + $criteria->descendantDist)
						);
					}
				}
			}

			if ($criteria->siblingOf)
			{
				if (!$criteria->siblingOf instanceof BaseElementModel)
				{
					$criteria->siblingOf = craft()->elements->getElementById($criteria->siblingOf, $elementType->getClassHandle());
				}

				if ($criteria->siblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.level = :siblingOf_level',
							'structureelements.root = :siblingOf_root',
							'structureelements.elementId != :siblingOf_elementId'
						),
						array(
							':siblingOf_level'     => $criteria->siblingOf->level,
							':siblingOf_root'      => $criteria->siblingOf->root,
							':siblingOf_elementId' => $criteria->siblingOf->id
						)
					);

					if ($criteria->siblingOf->level != 1)
					{
						$parent = $criteria->siblingOf->getParent();

						if ($parent)
						{
							$query->andWhere(
								array('and',
									'structureelements.lft > :siblingOf_lft',
									'structureelements.rgt < :siblingOf_rgt'
								),
								array(
									':siblingOf_lft'  => $parent->lft,
									':siblingOf_rgt'  => $parent->rgt
								)
							);
						}
						else
						{
							return false;
						}
					}
				}
			}

			if ($criteria->prevSiblingOf)
			{
				if (!$criteria->prevSiblingOf instanceof BaseElementModel)
				{
					$criteria->prevSiblingOf = craft()->elements->getElementById($criteria->prevSiblingOf, $elementType->getClassHandle());
				}

				if ($criteria->prevSiblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.rgt = :prevSiblingOf_rgt',
							'structureelements.root = :prevSiblingOf_root'
						),
						array(
							':prevSiblingOf_rgt'  => $criteria->prevSiblingOf->lft - 1,
							':prevSiblingOf_root' => $criteria->prevSiblingOf->root
						)
					);
				}
			}

			if ($criteria->nextSiblingOf)
			{
				if (!$criteria->nextSiblingOf instanceof BaseElementModel)
				{
					$criteria->nextSiblingOf = craft()->elements->getElementById($criteria->nextSiblingOf, $elementType->getClassHandle());
				}

				if ($criteria->nextSiblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.lft = :nextSiblingOf_lft',
							'structureelements.root = :nextSiblingOf_root'
						),
						array(
							':nextSiblingOf_lft'  => $criteria->nextSiblingOf->rgt + 1,
							':nextSiblingOf_root' => $criteria->nextSiblingOf->root
						)
					);
				}
			}

			if ($criteria->level || $criteria->depth)
			{
				// 'depth' is deprecated; use 'level' instead.
				$level = ($criteria->level ? $criteria->level : $criteria->depth);
				$query->andWhere(DbHelper::parseParam('structureelements.level', $level, $query->params));
			}
		}

		return $query;
	}

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
	 * Returns the locales that a given element is enabled in.
	 *
	 * @param int $elementId
	 * @return array
	 */
	public function getEnabledLocalesForElement($elementId)
	{
		return craft()->db->createCommand()
			->select('locale')
			->from('elements_i18n')
			->where(array('elementId' => $elementId, 'enabled' => 1))
			->queryColumn();
	}

	// Saving Elements
	// ===============

	/**
	 * Saves an element.
	 *
	 * @param BaseElementModel $element
	 * @param bool             $validateContent
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $validateContent = true)
	{
		$elementType = $this->getElementType($element->getElementType());

		// Validate the content first
		if ($validateContent && $elementType->hasContent() && !craft()->content->validateContent($element))
		{
			$element->addErrors($element->getContent()->getErrors());
			return false;
		}

		// Get the element record
		$isNewElement = !$element->id;

		if (!$isNewElement)
		{
			$elementRecord = ElementRecord::model()->findByAttributes(array(
				'id'   => $element->id,
				'type' => $element->getElementType()
			));

			if (!$elementRecord)
			{
				throw new Exception(Craft::t('No element exists with the ID “{id}”', array('id' => $element->id)));
			}
		}
		else
		{
			$elementRecord = new ElementRecord();
			$elementRecord->type = $element->getElementType();
		}

		// Set the attributes
		$elementRecord->enabled = (bool) $element->enabled;
		$elementRecord->archived = (bool) $element->archived;

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Save the element record first
			$success = $elementRecord->save(false);

			if ($success)
			{
				if ($isNewElement)
				{
					// Save the element id on the element model, in case {id} is in the URL format
					$element->id = $elementRecord->id;

					if ($elementType->hasContent())
					{
						$element->getContent()->elementId = $element->id;
					}
				}

				// Save the content
				if ($elementType->hasContent())
				{
					craft()->content->saveContent($element, false, (bool) $element->id);
				}

				// Update the search index
				craft()->search->indexElementAttributes($element);

				// Update the locale records and content

				// We're saving all of the element's locales here to ensure that they all exist
				// and to update the URI in the event that the URL format includes some value that just changed

				$localeRecords = array();

				if (!$isNewElement)
				{
					$existingLocaleRecords = ElementLocaleRecord::model()->findAllByAttributes(array(
						'elementId' => $element->id
					));

					foreach ($existingLocaleRecords as $record)
					{
						$localeRecords[$record->locale] = $record;
					}
				}

				$mainLocaleId = $element->locale;
				$mainSlug     = $element->slug;
				$mainUri      = $element->uri;

				if ($elementType->hasContent())
				{
					$mainContent = $element->getContent();
				}

				$localeIds = $element->getLocales();

				if (!$localeIds)
				{
					throw new Exception('All elements must have at least one locale associated with them.');
				}

				foreach ($localeIds as $localeId)
				{
					if (isset($localeRecords[$localeId]))
					{
						$localeRecord = $localeRecords[$localeId];
					}
					else
					{
						$localeRecord = new ElementLocaleRecord();
						$localeRecord->elementId = $element->id;
						$localeRecord->locale    = $localeId;
					}

					// Set the locale and its content on the element
					$element->locale = $localeId;

					if ($localeRecord->id && $localeRecord->locale != $mainLocaleId)
					{
						// Keep the original slug
						$element->slug = $localeRecord->slug;
					}
					else
					{
						$element->slug = $mainSlug;
					}

					if ($elementType->hasContent())
					{
						if ($localeId == $mainLocaleId)
						{
							$content = $mainContent;
						}
						else
						{
							$content = null;

							if (!$isNewElement)
							{
								// Do we already have a content row for this locale?
								$content = craft()->content->getContent($element);
							}

							if (!$content)
							{
								$content = craft()->content->createContent($element);
								$content->setAttributes($mainContent->getAttributes());
								$content->id = null;
								$content->locale = $localeId;
							}
						}

						$element->setContent($content);

						if (!$content->id)
						{
							craft()->content->saveContent($element, false, false);
						}
					}

					// Set a valid/unique slug and URI
					ElementHelper::setValidSlug($element);
					ElementHelper::setUniqueUri($element);

					$localeRecord->slug = $element->slug;
					$localeRecord->uri  = $element->uri;

					if ($localeId == $mainLocaleId)
					{
						$localeRecord->enabled = (bool) $element->localeEnabled;
					}

					$success = $localeRecord->save();

					if (!$success)
					{
						// Don't bother with any of the other locales
						break;
					}

					if ($localeId == $mainLocaleId)
					{
						// Remember the saved slug and URI
						$mainSlug = $element->slug;
						$mainUri  = $element->uri;
					}
				}

				// Bring everything back to this locale
				$element->locale = $mainLocaleId;
				$element->slug   = $mainSlug;
				$element->uri    = $mainUri;
				$element->setContent($mainContent);

				if ($success)
				{
					if (!$isNewElement)
					{
						// Delete the rows that don't need to be there anymore

						craft()->db->createCommand()->delete('elements_i18n', array('and',
							'elementId = :elementId',
							array('not in', 'locale', $localeIds)
						), array(
							':elementId' => $element->id
						));

						if ($elementType->hasContent())
						{
							craft()->db->createCommand()->delete($element->getContentTable(), array('and',
								'elementId = :elementId',
								array('not in', 'locale', $localeIds)
							), array(
								':elementId' => $element->id
							));
						}
					}

					// Call the field types' onAfterElementSave() methods
					$fieldLayout = $element->getFieldLayout();

					if ($fieldLayout)
					{
						foreach ($fieldLayout->getFields() as $fieldLayoutField)
						{
							$field = $fieldLayoutField->getField();

							if ($field)
							{
								$fieldType = $field->getFieldType();

								if ($fieldType)
								{
									$fieldType->element = $element;
									$fieldType->onAfterElementSave();
								}
							}
						}
					}
				}
			}

			if ($transaction !== null)
			{
				if ($success)
				{
					$transaction->commit();
				}
				else
				{
					$transaction->rollback();
				}
			}

			if (!$success && $isNewElement)
			{
				$element->id = null;

				if ($elementType->hasContent())
				{
					$element->getContent()->id = null;
					$element->getContent()->elementId = null;
				}
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		return $success;
	}

	/**
	 * Updates an element's slug and URI, along with any descendants.
	 *
	 * @param BaseElementModel $element
	 * @param bool $updateOtherLocales
	 * @param bool $updateDescendants
	 */
	public function updateElementSlugAndUri(BaseElementModel $element, $updateOtherLocales = true, $updateDescendants = true)
	{
		ElementHelper::setUniqueUri($element);

		craft()->db->createCommand()->update('elements_i18n', array(
			'slug' => $element->slug,
			'uri'  => $element->uri
		), array(
			'elementId' => $element->id,
			'locale'    => $element->locale
		));

		if ($updateOtherLocales)
		{
			$this->updateElementSlugAndUriInOtherLocales($element);
		}

		if ($updateDescendants)
		{
			$this->updateDescendantSlugsAndUris($element);
		}
	}

	/**
	 * Updates an element's slug and URI, for any locales besides the given one.
	 *
	 * @param BaseElementModel $element
	 */
	public function updateElementSlugAndUriInOtherLocales(BaseElementModel $element)
	{
		foreach (craft()->i18n->getSiteLocaleIds() as $localeId)
		{
			if ($localeId == $element->locale)
			{
				continue;
			}

			$criteria = $this->getCriteria($element->getElementType());
			$criteria->id = $element->id;
			$criteria->locale = $localeId;
			$criteria->status = null;
			$criteria->localeEnabled = null;
			$elementInOtherLocale = $criteria->first();

			if ($elementInOtherLocale)
			{
				$this->updateElementSlugAndUri($elementInOtherLocale, false, false);
			}
		}
	}

	/**
	 * Updates an element's descendants' slugs and URIs.
	 *
	 * @param BaseElementModel $element
	 */
	public function updateDescendantSlugsAndUris(BaseElementModel $element)
	{
		$criteria = $this->getCriteria($element->getElementType());
		$criteria->descendantOf = $element;
		$criteria->descendantDist = 1;
		$criteria->status = null;
		$criteria->localeEnabled = null;
		$children = $criteria->find();

		foreach ($children as $child)
		{
			$this->updateElementSlugAndUri($child);
		}
	}

	/**
	 * Resaves all of the elements matching the given criteria.
	 * Useful for patching missing locale rows, etc.
	 *
	 * @param ElementCriteriaModel $criteria
	 */
	public function resaveElements(ElementCriteriaModel $criteria)
	{
		// This might take a while
		craft()->config->maxPowerCaptain();
		ignore_user_abort(true);

		// Just to be safe...
		$criteria->locale = craft()->i18n->getPrimarySiteLocaleId();
		$criteria->status = null;
		$criteria->localeEnabled = null;
		$criteria->order = 'dateCreated asc';

		// Do this in batches so we don't hit the memory limit
		$criteria->offset = 0;
		$criteria->limit = 25;

		do
		{
			$batchElements = $criteria->find();

			foreach ($batchElements as $element)
			{
				$this->saveElement($element, false);
			}

			$criteria->offset += 25;
		}
		while ($batchElements);
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
