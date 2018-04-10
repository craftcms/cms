<?php
namespace Craft;

/**
 * ElementsService provides APIs for managing elements.
 *
 * An instance of ElementsService is globally accessible in Craft via {@link WebApp::elements `craft()->elements`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class ElementsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_placeholderElements;

	/**
	 * @var array
	 */
	private $_searchResults;

	/**
	 * @var array
	 */
	private $_elementCleanup = array();

	// Public Methods
	// =========================================================================

	// Finding Elements
	// -------------------------------------------------------------------------

	/**
	 * Returns an element criteria model for a given element type.
	 *
	 * This should be the starting point any time you want to fetch elements in Craft.
	 *
	 * ```php
	 * $criteria = craft()->elements->getCriteria(ElementType::Entry);
	 * $criteria->section = 'news';
	 * $entries = $criteria->find();
	 * ```
	 *
	 * @param string $type       The element type class handle (e.g. one of the values in the {@link ElementType} enum).
	 * @param mixed  $attributes Any criteria attribute values that should be pre-populated on the criteria model.
	 *
	 * @throws Exception
	 * @return ElementCriteriaModel An element criteria model, wired to fetch elements of the given $type.
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
	 * If no element type is provided, the method will first have to run a DB query to determine what type of element
	 * the $elementId is, so you should definitely pass it if it’s known.
	 *
	 * The element’s status will not be a factor when using this method.
	 *
	 * @param int    $elementId   The element’s ID.
	 * @param null   $elementType The element type’s class handle.
	 * @param string $localeId    The locale to fetch the element in.
	 *                            Defaults to {@link WebApp::language `craft()->language`}.
	 *
	 * @return BaseElementModel|null The matching element, or `null`.
	 */
	public function getElementById($elementId, $elementType = null, $localeId = null)
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
		$criteria->locale = $localeId;
		$criteria->status = null;
		$criteria->localeEnabled = null;
		return $criteria->first();
	}

	/**
	 * Returns an element by its URI.
	 *
	 * @param string      $uri         The element’s URI.
	 * @param string|null $localeId    The locale to look for the URI in, and to return the element in.
	 *                                 Defaults to {@link WebApp::language `craft()->language`}.
	 * @param bool        $enabledOnly Whether to only look for an enabled element. Defaults to `false`.
	 *
	 * @return BaseElementModel|null The matching element, or `null`.
	 */
	public function getElementByUri($uri, $localeId = null, $enabledOnly = false)
	{
		if ($uri === '')
		{
			$uri = '__home__';
		}

		if (!$localeId)
		{
			$localeId = craft()->language;
		}

		// First get the element ID and type

		$conditions = array('and',
			'elements_i18n.uri = :uri',
			'elements_i18n.locale = :locale'
		);

		$params = array(
			':uri'    => $uri,
			':locale' => $localeId
		);

		if ($enabledOnly)
		{
			$conditions[] = 'elements_i18n.enabled = 1';
			$conditions[] = 'elements.enabled = 1';
			$conditions[] = 'elements.archived = 0';
		}

		$result = craft()->db->createCommand()
			->select('elements.id, elements.type')
			->from('elements elements')
			->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id')
			->where($conditions, $params)
			->queryRow();

		if ($result)
		{
			// Return the actual element
			return $this->getElementById($result['id'], $result['type'], $localeId);
		}
	}

	/**
	 * Returns the element type(s) used by the element of a given ID(s).
	 *
	 * If a single ID is passed in (an int), then a single element type will be returned (a string), or `null` if
	 * no element exists by that ID.
	 *
	 * If an array is passed in, then an array will be returned.
	 *
	 * @param int|array $elementId An element’s ID, or an array of elements’ IDs.
	 *
	 * @return string|array|null The element type(s).
	 */
	public function getElementTypeById($elementId)
	{
		if (is_array($elementId))
		{
			return craft()->db->createCommand()
				->selectDistinct('type')
				->from('elements')
				->where(array('in', 'id', $elementId))
				->queryColumn();
		}
		else
		{
			return craft()->db->createCommand()
				->select('type')
				->from('elements')
				->where(array('id' => $elementId))
				->queryScalar();
		}
	}

	/**
	 * Finds elements.
	 *
	 * @param ElementCriteriaModel $criteria An element criteria model that defines the parameters for the elements
	 *                                       we should be looking for.
	 * @param bool                 $justIds  Whether the method should only return an array of the IDs of the matched
	 *                                       elements. Defaults to `false`.
	 *
	 * @return array The matched elements, or their IDs, depending on $justIds.
	 */
	public function findElements($criteria = null, $justIds = false)
	{
		// Create an element query based on this criteria
		$query = $this->buildElementsQuery($criteria, $contentTable, $fieldColumns, $justIds);

		if (!$query)
		{
			// Something decided that executing the query is pointless
			return array();
		}

		if ($justIds)
		{
			return $query->queryColumn();
		}

		$results = $query->queryAll();

		if (!$results)
		{
			return array();
		}

		return $this->populateElements($results, $criteria, $contentTable, $fieldColumns);
	}

	/**
	 * Populates element models from a given element query's result set.
	 *
	 * @param array                $results      The result set of an element query
	 * @param ElementCriteriaModel $criteria     The element criteria model
	 * @param string               $contentTable The content table that was joined in by buildElementsQuery()
	 * @param array                $fieldColumns Info about the content field columns being selected
	 *
	 * @return BaseElementModel[] The populated element models.
	 */
	public function populateElements($results, ElementCriteriaModel $criteria, $contentTable, $fieldColumns)
	{
		$elements = array();

		$locale = $criteria->locale;
		$elementType = $criteria->getElementType();
		$indexBy = $criteria->indexBy;

		foreach ($results as $result)
		{
			// Do we have a placeholder for this element?
			if (isset($this->_placeholderElements[$result['id']][$locale]))
			{
				$element = $this->_placeholderElements[$result['id']][$locale];
			}
			else
			{
				// Make a copy to pass to the onPopulateElement event
				$originalResult = array_merge($result);

				if ($contentTable)
				{
					// Separate the content values from the main element attributes
					$content = array(
						'id'        => (isset($result['contentId']) ? $result['contentId'] : null),
						'elementId' => $result['id'],
						'locale'    => $locale,
						'title'     => (isset($result['title']) ? $result['title'] : null)
					);

					unset($result['title']);

					if ($fieldColumns)
					{
						foreach ($fieldColumns as $column)
						{
							// Account for results where multiple fields have the same handle, but from
							// different columns e.g. two Matrix block types that each have a field with the
							// same handle

							$colName = $column['column'];
							$fieldHandle = $column['handle'];

							if (!isset($content[$fieldHandle]) || (empty($content[$fieldHandle]) && !empty($result[$colName])))
							{
								$content[$fieldHandle] = $result[$colName];
							}

							unset($result[$colName]);
						}
					}
				}

				$result['locale'] = $locale;

				// Should we set a search score on the element?
				if (isset($this->_searchResults[$result['id']]))
				{
					$result['searchScore'] = $this->_searchResults[$result['id']];
				}

				$element = $elementType->populateElementModel($result);

				// Was an element returned?
				if (!$element || !($element instanceof BaseElementModel))
				{
					continue;
				}

				if ($contentTable)
				{
					$element->setContent($content);
				}

				// Fire an 'onPopulateElement' event
				$this->onPopulateElement(new Event($this, array(
					'element' => $element,
					'result'  => $originalResult
				)));
			}

			if ($indexBy)
			{
				// Cast to a string in the case of SingleOptionFieldData, so its
				// __toString() method gets invoked.
				$elements[(string)$element->$indexBy] = $element;
			}
			else
			{
				$elements[] = $element;
			}
		}

		ElementHelper::setNextPrevOnElements($elements);

		// Should we eager-load some elements onto these?
		if ($criteria->with)
		{
			$this->eagerLoadElements($elementType, $elements, $criteria->with);
		}

		// Fire an 'onPopulateElements' event
		$this->onPopulateElements(new Event($this, array(
			'elements' => $elements,
			'criteria' => $criteria
		)));

		// Fire the criteria's 'onPopulateElements' event
		$criteria->onPopulateElements(new Event($criteria, array(
			'elements' => $elements
		)));

		return $elements;
	}

	/**
	 * Eager-loads additional elements onto a given set of elements.
	 *
	 * @param BaseElementType    $elementType The root element type
	 * @param BaseElementModel[] $elements    The root element models that should be updated with the eager-loaded elements
	 * @param string|array       $with        Dot-delimited paths of the elements that should be eager-loaded into the root elements
	 *
	 * @return void
	 */
	public function eagerLoadElements(BaseElementType $elementType, $elements, $with)
	{
		// Bail if there aren't even any elements
		if (!$elements)
		{
			return;
		}

		// Normalize the paths and find any custom path criterias
		$with = ArrayHelper::stringToArray($with);
		$paths = array();
		$pathCriterias = array();

		foreach ($with as $path)
		{
			// Using the array syntax?
			// ['foo.bar'] or ['foo.bar', criteria]
			if (is_array($path))
			{
				if (!empty($path[1]))
				{
					$pathCriterias['__root__.'.$path[0]] = $path[1];
				}

				$paths[] = $path[0];
			}
			else
			{
				$paths[] = $path;
			}
		}

		// Load 'em up!
		$elementsByPath = array('__root__' => $elements);
		$elementTypesByPath = array('__root__' => $elementType->getClassHandle());

		foreach ($paths as $path)
		{
			$pathSegments = explode('.', $path);
			$sourcePath = '__root__';

			foreach ($pathSegments as $segment)
			{
				$targetPath = $sourcePath.'.'.$segment;

				// Figure out the path mapping wants a custom order
				$useCustomOrder = !empty($pathCriterias[$targetPath]['order']);

				// Make sure we haven't already eager-loaded this target path
				if (!isset($elementsByPath[$targetPath]))
				{
					// Guilty until proven innocent
					$elementsByPath[$targetPath] = $targetElements = $targetElementsById = $targetElementIdsBySourceIds = false;

					// Get the eager-loading map from the source element type
					$sourceElementType = $this->getElementType($elementTypesByPath[$sourcePath]);
					$map = $sourceElementType->getEagerLoadingMap($elementsByPath[$sourcePath], $segment);

					if ($map && !empty($map['map']))
					{
						// Remember the element type in case there are more segments after this
						$elementTypesByPath[$targetPath] = $map['elementType'];

						// Loop through the map to find:
						// - unique target element IDs
						// - target element IDs indexed by source element IDs
						$uniqueTargetElementIds = array();
						$targetElementIdsBySourceIds = array();

						foreach ($map['map'] as $mapping)
						{
							if (!in_array($mapping['target'], $uniqueTargetElementIds))
							{
								$uniqueTargetElementIds[] = $mapping['target'];
							}

							$targetElementIdsBySourceIds[$mapping['source']][] = $mapping['target'];
						}

						// Get the target elements
						$customParams = array_merge(
						// Default to no order and limit, but allow the element type/path criteria to override
							array('order' => null, 'limit' => null),
							(isset($map['criteria']) ? $map['criteria'] : array()),
							(isset($pathCriterias[$targetPath]) ? $pathCriterias[$targetPath] : array())
						);
						$criteria = $this->getCriteria($map['elementType'], $customParams);
						if ($criteria->id)
						{
							$criteria->id = array_intersect((array)$criteria->id, $uniqueTargetElementIds);
						}
						else
						{
							$criteria->id = $uniqueTargetElementIds;
						}
						$targetElements = $this->findElements($criteria);

						if ($targetElements)
						{
							// Success! Store those elements on $elementsByPath FFR
							$elementsByPath[$targetPath] = $targetElements;

							// Index the target elements by their IDs if we are using the map-defined order
							if (!$useCustomOrder)
							{
								$targetElementsById = array();

								foreach ($targetElements as $targetElement)
								{
									$targetElementsById[$targetElement->id] = $targetElement;
								}
							}
						}
					}

					// Tell the source elements about their eager-loaded elements (or lack thereof, as the case may be)
					foreach ($elementsByPath[$sourcePath] as $sourceElement)
					{
						$sourceElementId = $sourceElement->id;
						$targetElementsForSource = array();

						if (isset($targetElementIdsBySourceIds[$sourceElementId]))
						{
							if ($useCustomOrder)
							{
								// Assign the elements in the order they were returned from the query
								foreach ($targetElements as $targetElement)
								{
									if (in_array($targetElement->id, $targetElementIdsBySourceIds[$sourceElementId]))
									{
										$targetElementsForSource[] = $targetElement;
									}
								}
							}
							else
							{
								// Assign the elements in the order defined by the map
								foreach ($targetElementIdsBySourceIds[$sourceElementId] as $targetElementId)
								{
									if (isset($targetElementsById[$targetElementId]))
									{
										$targetElementsForSource[] = $targetElementsById[$targetElementId];
									}
								}
							}
						}

						$sourceElement->setEagerLoadedElements($segment, $targetElementsForSource);
					}
				}

				if (!$elementsByPath[$targetPath])
				{
					// Dead end - stop wasting time on this path
					break;
				}

				// Update the source path
				$sourcePath = $targetPath;
			}
		}
	}

	/**
	 * Returns the total number of elements that match a given criteria.
	 *
	 * @param ElementCriteriaModel $criteria An element criteria model that defines the parameters for the elements
	 *                                       we should be counting.
	 *
	 * @return int The total number of elements that match the criteria.
	 */
	public function getTotalElements($criteria = null)
	{
		// TODO: Lots in here MySQL specific.
		$query = $this->buildElementsQuery($criteria, $contentTable, $fieldColumns, true);

		if ($query)
		{
			$query
				->order('')
				->offset(0)
				->limit(-1)
				->from('elements elements');

			$elementsIdColumn = 'elements.id';
			$elementsIdColumnAlias = 'elementsId';
			$selectedColumns = $query->getSelect();

			// Normalize with no quotes. setSelect later will properly add them back in.
			$selectedColumns = str_replace('`', '', $selectedColumns);

			// Guarantee we select an elements.id column
			if (strpos($selectedColumns, $elementsIdColumn) === false)
			{
				$selectedColumns = $elementsIdColumn.', '.$selectedColumns;
			}

			// Replace all instances of elements.id with elementsId
			$selectedColumns = str_replace($elementsIdColumn, $elementsIdColumnAlias, $selectedColumns);

			// Find the position of the first occurrence of elementsId
			$pos = strpos($selectedColumns, $elementsIdColumnAlias);

			// Make the first occurrence of elementsId an alias for elements.id
			if ($pos !== false)
			{
				$selectedColumns = substr_replace($selectedColumns, $elementsIdColumn.' AS '.$elementsIdColumnAlias, $pos, strlen($elementsIdColumnAlias));
			}

			$query->setSelect($selectedColumns);
			$masterQuery = craft()->db->createCommand();
			$masterQuery->params = $query->params;
			$masterQuery->from(sprintf('(%s) derivedElementsTable', $query->getText()));
			$count = $masterQuery->count('derivedElementsTable.'.$elementsIdColumnAlias);

			return $count;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Preps a {@link DbCommand} object for querying for elements, based on a given element criteria.
	 *
	 * @param ElementCriteriaModel &$criteria     The element criteria model
	 * @param string               &$contentTable The content table that should be joined in. (This variable will
	 *                                            actually get defined by buildElementsQuery(), and is passed by
	 *                                            reference so whatever’s calling the method will have access to its
	 *                                            value.)
	 * @param array                &$fieldColumns Info about the content field columns being selected. (This variable
	 *                                            will actually get defined by buildElementsQuery(), and is passed by
	 *                                            reference so whatever’s calling the method will have access to its
	 *                                            value.)
	 * @param bool                 $justIds       Whether the method should only return an array of the IDs of the
	 *                                            matched elements. Defaults to `false`.
	 *
	 * @return DbCommand|false The DbCommand object, or `false` if the method was able to determine ahead of time that
	 *                         there’s no chance any elements are going to be found with the given parameters.
	 */
	public function buildElementsQuery(&$criteria = null, &$contentTable = null, &$fieldColumns = null, $justIds = false)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = $this->getCriteria('Entry', $criteria);
		}

		$elementType = $criteria->getElementType();

		if (!$elementType->isLocalized())
		{
			// The criteria *must* be set to the primary locale
			$criteria->locale = craft()->i18n->getPrimarySiteLocaleId();
		}
		else if (!$criteria->locale)
		{
			// Default to the current app locale
			$criteria->locale = craft()->language;
		}

		// Set up the query
		// ---------------------------------------------------------------------

		// Create the DbCommand object
		$query = craft()->db->createCommand();

		// Fire an 'onBeforeBuildElementsQuery' event
		$event = new Event($this, array(
			'criteria' => $criteria,
			'justIds' => $justIds,
			'query' => $query
		));

		$this->onBeforeBuildElementsQuery($event);

		// Did any of the event handlers object to this query?
		if (!$event->performAction)
		{
			return false;
		}

		if ($justIds)
		{
			$query->select('elements.id');
		}
		else
		{
			$query->select('elements.id, elements.type, elements.enabled, elements.archived, elements.dateCreated, elements.dateUpdated, elements_i18n.slug, elements_i18n.uri, elements_i18n.enabled AS localeEnabled');
		}

		$query
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

				// TODO: Replace this with a call to getFieldsForElementsQuery() in 3.0
				$fieldColumns = $elementType->getContentFieldColumnsForElementsQuery($criteria);

				foreach ($fieldColumns as $column)
				{
					$contentCols .= ', content.'.$column['column'];
				}

				if (!$justIds)
				{
					$query->addSelect($contentCols);
				}

				$query->join($contentTable.' content', 'content.elementId = elements.id');
				$query->andWhere('content.locale = :locale');
			}
		}

		// Basic element params
		// ---------------------------------------------------------------------

		// If the 'id' parameter is set to any empty value besides `null`, don't return anything
		if ($criteria->id !== null && empty($criteria->id))
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
					$status = StringHelper::toLowerCase($status);

					// Is this a supported status?
					if (in_array($status, array_keys($elementType->getStatuses())))
					{
						if ($status == BaseElementModel::ENABLED)
						{
							$statusConditions[] = 'elements.enabled = 1';
						}
						else if ($status == BaseElementModel::DISABLED)
						{
							$statusConditions[] = 'elements.enabled = 0';
						}
						else
						{
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

		if ($elementType->hasTitles() && $criteria->title)
		{
			$query->andWhere(DbHelper::parseParam('content.title', $criteria->title, $query->params));
		}

		// i18n params
		// ---------------------------------------------------------------------

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
		// ---------------------------------------------------------------------

		// Convert the old childOf and parentOf params to the relatedTo param
		// childOf(element)  => relatedTo({ sourceElement: element })
		// parentOf(element) => relatedTo({ targetElement: element })
		if (!$criteria->relatedTo && ($criteria->childOf || $criteria->parentOf))
		{
			$relatedTo = array('and');

			if ($criteria->childOf)
			{
				$relatedTo[] = array('sourceElement' => $criteria->childOf, 'field' => $criteria->childField);
			}

			if ($criteria->parentOf)
			{
				$relatedTo[] = array('targetElement' => $criteria->parentOf, 'field' => $criteria->parentField);
			}

			$criteria->relatedTo = $relatedTo;
			craft()->deprecator->log('element_old_relation_params', 'The ‘childOf’, ‘childField’, ‘parentOf’, and ‘parentField’ element params have been deprecated. Use ‘relatedTo’ instead.');
		}

		if ($criteria->relatedTo)
		{
			$relationParamParser = new ElementRelationParamParser();
			$relConditions = $relationParamParser->parseRelationParam($criteria->relatedTo, $query);

			if ($relConditions === false)
			{
				return false;
			}

			$query->andWhere($relConditions);

			// If there's only one relation criteria and it's specifically for grabbing target elements, allow the query
			// to order by the relation sort order
			if (!$justIds && $relationParamParser->isRelationFieldQuery())
			{
				$query->addSelect('sources1.sortOrder');
			}
		}

		// Give field types a chance to make changes
		// ---------------------------------------------------------------------

		if ($elementType->hasContent() && $contentTable)
		{
			$contentService = craft()->content;
			$originalFieldColumnPrefix = $contentService->fieldColumnPrefix;

			// TODO: $fields should already be defined by now in Craft 3.0
			$fields = $elementType->getFieldsForElementsQuery($criteria);
			$extraCriteriaAttributes = $criteria->getExtraAttributeNames();

			foreach ($fields as $field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType)
				{
					// Was this field's parameter set on the criteria model?
					if (in_array($field->handle, $extraCriteriaAttributes))
					{
						$fieldCriteria = $criteria->{$field->handle};
					}
					else
					{
						$fieldCriteria = null;
					}

					// Set the field's column prefix on ContentService
					if ($field->columnPrefix)
					{
						$contentService->fieldColumnPrefix = $field->columnPrefix;
					}

					$fieldTypeResponse = $fieldType->modifyElementsQuery($query, $fieldCriteria);

					// Set it back
					$contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

					// Need to bail early?
					if ($fieldTypeResponse === false)
					{
						return false;
					}
				}
			}
		}

		// Give the element type a chance to make changes
		// ---------------------------------------------------------------------

		if ($elementType->modifyElementsQuery($query, $criteria) === false)
		{
			return false;
		}

		// Structure params
		// ---------------------------------------------------------------------

		if ($query->isJoined('structureelements'))
		{
			if (!$justIds)
			{
				$query->addSelect('structureelements.root, structureelements.lft, structureelements.rgt, structureelements.level');
			}

			if ($criteria->ancestorOf)
			{
				if (!$criteria->ancestorOf instanceof BaseElementModel)
				{
					$criteria->ancestorOf = craft()->elements->getElementById($criteria->ancestorOf, $elementType->getClassHandle(), $criteria->locale);

					if (!$criteria->ancestorOf)
					{
						return false;
					}
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
					$criteria->descendantOf = craft()->elements->getElementById($criteria->descendantOf, $elementType->getClassHandle(), $criteria->locale);

					if (!$criteria->descendantOf)
					{
						return false;
					}
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
					$criteria->siblingOf = craft()->elements->getElementById($criteria->siblingOf, $elementType->getClassHandle(), $criteria->locale);

					if (!$criteria->siblingOf)
					{
						return false;
					}
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
					$criteria->prevSiblingOf = craft()->elements->getElementById($criteria->prevSiblingOf, $elementType->getClassHandle(), $criteria->locale);

					if (!$criteria->prevSiblingOf)
					{
						return false;
					}
				}

				if ($criteria->prevSiblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.level = :prevSiblingOf_level',
							'structureelements.rgt = :prevSiblingOf_rgt',
							'structureelements.root = :prevSiblingOf_root'
						),
						array(
							':prevSiblingOf_level' => $criteria->prevSiblingOf->level,
							':prevSiblingOf_rgt'   => $criteria->prevSiblingOf->lft - 1,
							':prevSiblingOf_root'  => $criteria->prevSiblingOf->root
						)
					);
				}
			}

			if ($criteria->nextSiblingOf)
			{
				if (!$criteria->nextSiblingOf instanceof BaseElementModel)
				{
					$criteria->nextSiblingOf = craft()->elements->getElementById($criteria->nextSiblingOf, $elementType->getClassHandle(), $criteria->locale);

					if (!$criteria->nextSiblingOf)
					{
						return false;
					}
				}

				if ($criteria->nextSiblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.level = :nextSiblingOf_level',
							'structureelements.lft = :nextSiblingOf_lft',
							'structureelements.root = :nextSiblingOf_root'
						),
						array(
							':nextSiblingOf_level' => $criteria->nextSiblingOf->level,
							':nextSiblingOf_lft'   => $criteria->nextSiblingOf->rgt + 1,
							':nextSiblingOf_root'  => $criteria->nextSiblingOf->root
						)
					);
				}
			}

			if ($criteria->positionedBefore)
			{
				if (!$criteria->positionedBefore instanceof BaseElementModel)
				{
					$criteria->positionedBefore = craft()->elements->getElementById($criteria->positionedBefore, $elementType->getClassHandle(), $criteria->locale);

					if (!$criteria->positionedBefore)
					{
						return false;
					}
				}

				if ($criteria->positionedBefore)
				{
					$query->andWhere(
						array('and',
							'structureelements.rgt < :positionedBefore_rgt',
							'structureelements.root = :positionedBefore_root'
						),
						array(
							':positionedBefore_rgt'   => $criteria->positionedBefore->lft,
							':positionedBefore_root'  => $criteria->positionedBefore->root
						)
					);
				}
			}

			if ($criteria->positionedAfter)
			{
				if (!$criteria->positionedAfter instanceof BaseElementModel)
				{
					$criteria->positionedAfter = craft()->elements->getElementById($criteria->positionedAfter, $elementType->getClassHandle(), $criteria->locale);

					if (!$criteria->positionedAfter)
					{
						return false;
					}
				}

				if ($criteria->positionedAfter)
				{
					$query->andWhere(
						array('and',
							'structureelements.lft > :positionedAfter_lft',
							'structureelements.root = :positionedAfter_root'
						),
						array(
							':positionedAfter_lft'   => $criteria->positionedAfter->rgt,
							':positionedAfter_root'  => $criteria->positionedAfter->root
						)
					);
				}
			}

			if (!$criteria->level && $criteria->depth)
			{
				$criteria->level = $criteria->depth;
				$criteria->depth = null;
				craft()->deprecator->log('element_depth_param', 'The \'depth\' element param has been deprecated. Use \'level\' instead.');
			}

			if ($criteria->level)
			{
				$query->andWhere(DbHelper::parseParam('structureelements.level', $criteria->level, $query->params));
			}
		}

		// Search
		// ---------------------------------------------------------------------

		$this->_searchResults = null;

		if ($criteria->search)
		{
			$elementIds = $this->_getElementIdsFromQuery($query);
			$searchResults = craft()->search->filterElementIdsByQuery($elementIds, $criteria->search, true, $criteria->locale, true);

			// No results?
			if (!$searchResults)
			{
				return false;
			}

			$filteredElementIds = array_keys($searchResults);

			if ($criteria->order == 'score')
			{
				// Order the elements in the exact order that SearchService returned them in
				$query->order(craft()->db->getSchema()->orderByColumnValues('elements.id', $filteredElementIds));
			}

			$query->andWhere(array('in', 'elements.id', $filteredElementIds));

			$this->_searchResults = $searchResults;
		}

		// Order
		// ---------------------------------------------------------------------

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
			$order = $criteria->order;
			$orderColumnMap = array();

			if (is_array($fieldColumns))
			{
				// Add the field column prefixes
				foreach ($fieldColumns as $column)
				{
					$orderColumnMap[$column['handle']] = $column['column'];
				}
			}

			// Prevent “1052 Column 'id' in order clause is ambiguous” MySQL error
			$orderColumnMap['id'] = 'elements.id';

			foreach ($orderColumnMap as $orderValue => $columnName)
			{
				// Avoid matching fields named "asc" or "desc" in the string "column_name asc" or
				// "column_name desc"
				$order = preg_replace('/(?<!\w\s|\.)\b'.$orderValue.'\b/', $columnName.'$1', $order);
			}

			$query->order($order);
		}

		// Offset and Limit
		// ---------------------------------------------------------------------

		if ($criteria->offset)
		{
			$query->offset($criteria->offset);
		}

		if ($criteria->limit)
		{
			$query->limit($criteria->limit);
		}

		// Fire an 'onBuildElementsQuery' event
		$this->onBuildElementsQuery(new Event($this, array(
			'criteria' => $criteria,
			'justIds' => $justIds,
			'query' => $query
		)));

		return $query;
	}

	/**
	 * Returns an element’s URI for a given locale.
	 *
	 * @param int    $elementId The element’s ID.
	 * @param string $localeId  The locale to search for the element’s URI in.
	 *
	 * @return string|null The element’s URI, or `null`.
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
	 * @param int $elementId The element’s ID.
	 *
	 * @return array The locales that the element is enabled in. If the element could not be found, an empty array
	 *               will be returned.
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
	// -------------------------------------------------------------------------

	/**
	 * Handles all of the routine tasks that go along with saving elements.
	 *
	 * Those tasks include:
	 *
	 * - Validating its content (if $validateContent is `true`, or it’s left as `null` and the element is enabled)
	 * - Ensuring the element has a title if its type {@link BaseElementType::hasTitles() has titles}, and giving it a
	 *   default title in the event that $validateContent is set to `false`
	 * - Saving a row in the `elements` table
	 * - Assigning the element’s ID on the element model, if it’s a new element
	 * - Assigning the element’s ID on the element’s content model, if there is one and it’s a new set of content
	 * - Updating the search index with new keywords from the element’s content
	 * - Setting a unique URI on the element, if it’s supposed to have one.
	 * - Saving the element’s row(s) in the `elements_i18n` and `content` tables
	 * - Deleting any rows in the `elements_i18n` and `content` tables that no longer need to be there
	 * - Calling the field types’ {@link BaseFieldType::onAfterElementSave() onAfterElementSave()} methods
	 * - Cleaing any template caches that the element was involved in
	 *
	 * This method should be called by a service’s “saveX()” method, _after_ it is done validating any attributes on
	 * the element that are of particular concern to its element type. For example, if the element were an entry,
	 * saveElement() should be called only after the entry’s sectionId and typeId attributes had been validated to
	 * ensure that they point to valid section and entry type IDs.
	 *
	 * @param BaseElementModel $element         The element that is being saved
	 * @param bool|null        $validateContent Whether the element's content should be validated. If left 'null', it
	 *                                          will depend on whether the element is enabled or not.
	 *
	 * @throws Exception|\Exception
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $validateContent = null)
	{
		$elementType = $this->getElementType($element->getElementType());

		$isNewElement = !$element->id;

		// Validate the content first
		if ($elementType->hasContent())
		{
			if ($validateContent === null)
			{
				$validateContent = (bool) $element->enabled;
			}

			if ($validateContent && !craft()->content->validateContent($element))
			{
				$element->addErrors($element->getContent()->getErrors());
				return false;
			}
			else
			{
				// Make sure there's a title
				if ($elementType->hasTitles())
				{
					$fields = array('title');
					$content = $element->getContent();
					$content->setRequiredFields($fields);

					if (!$content->validate($fields) && $content->hasErrors('title'))
					{
						// Just set *something* on it
						if ($isNewElement)
						{
							$content->title = 'New '.$element->getClassHandle();
						}
						else
						{
							$content->title = $element->getClassHandle().' '.$element->id;
						}
					}
				}
			}
		}

		// Get the element record
		if (!$isNewElement)
		{
			$elementRecord = ElementRecord::model()->findByAttributes(array(
				'id'   => $element->id,
				'type' => $element->getElementType()
			));

			if (!$elementRecord)
			{
				throw new Exception(Craft::t('No element exists with the ID “{id}”.', array('id' => $element->id)));
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
			// Fire an 'onBeforeSaveElement' event
			$event = new Event($this, array(
				'element'      => $element,
				'isNewElement' => $isNewElement
			));

			$this->onBeforeSaveElement($event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element record first
				$success = $elementRecord->save(false);

				if ($success)
				{
					// Save the new dateCreated and dateUpdated dates on the model
					$element->dateCreated = new DateTime('@'.$elementRecord->dateCreated->getTimestamp());
					$element->dateUpdated = new DateTime('@'.$elementRecord->dateUpdated->getTimestamp());

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
						craft()->content->saveContent($element, false, (bool)$element->id);
					}

					// Update the search index
					craft()->search->indexElementAttributes($element);

					// Update the locale records and content

					// We're saving all of the element's locales here to ensure that they all exist and to update the URI in
					// the event that the URL format includes some value that just changed

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

					$locales = $element->getLocales();
					$localeIds = array();

					if (!$locales)
					{
						throw new Exception('All elements must have at least one locale associated with them.');
					}

					foreach ($locales as $localeId => $localeInfo)
					{
						if (is_numeric($localeId) && is_string($localeInfo))
						{
							$localeId = $localeInfo;
							$localeInfo = array();
						}

						$localeIds[] = $localeId;

						if (!isset($localeInfo['enabledByDefault']))
						{
							$localeInfo['enabledByDefault'] = true;
						}

						if (isset($localeRecords[$localeId]))
						{
							$localeRecord = $localeRecords[$localeId];
						}
						else
						{
							$localeRecord = new ElementLocaleRecord();

							$localeRecord->elementId = $element->id;
							$localeRecord->locale = $localeId;
							$localeRecord->enabled = $localeInfo['enabledByDefault'];
						}

						// Is this the main locale?
						$isMainLocale = ($localeId == $mainLocaleId);

						if ($isMainLocale)
						{
							$localizedElement = $element;
						}
						else
						{
							// Copy the element for this locale
							$localizedElement = $element->copy();
							$localizedElement->locale = $localeId;

							if ($localeRecord->id)
							{
								// Keep the original slug
								$localizedElement->slug = $localeRecord->slug;
							}
							else
							{
								// Default to the main locale's slug
								$localizedElement->slug = $element->slug;
							}
						}

						if ($elementType->hasContent())
						{
							if (!$isMainLocale)
							{
								$content = null;

								if (!$isNewElement)
								{
									// Do we already have a content row for this locale?
									$content = craft()->content->getContent($localizedElement);
								}

								if (!$content)
								{
									$content = craft()->content->createContent($localizedElement);
									$content->setAttributes($element->getContent()->getAttributes());
									$content->id = null;
									$content->locale = $localeId;
								}

								$localizedElement->setContent($content);
							}

							if (!$localizedElement->getContent()->id)
							{
								craft()->content->saveContent($localizedElement, false, false);
							}
						}

						// Capture the original slug, in case it's entirely composed of invalid characters
						$originalSlug = $localizedElement->slug;

						// Clean up the slug
						ElementHelper::setValidSlug($localizedElement);

						// If the slug was entirely composed of invalid characters, it will be blank now.
						if ($originalSlug && !$localizedElement->slug)
						{
							$localizedElement->slug = $originalSlug;
							$element->addError('slug', Craft::t('{attribute} is invalid.', array('attribute' => Craft::t('Slug'))));

							// Don't bother with any of the other locales
							$success = false;
							break;
						}

						// Go ahead and re-do search index keywords to grab things like "title" in multi-locale installs.
						if ($isNewElement)
						{
							craft()->search->indexElementAttributes($localizedElement);
						}

						ElementHelper::setUniqueUri($localizedElement);

						$localeRecord->slug = $localizedElement->slug;
						$localeRecord->uri = $localizedElement->uri;

						if ($isMainLocale)
						{
							$localeRecord->enabled = (bool)$element->localeEnabled;
						}

						$success = $localeRecord->save();

						if (!$success)
						{
							// Pass any validation errors on to the element
							$element->addErrors($localeRecord->getErrors());

							// Don't bother with any of the other locales
							break;
						}
					}

					if ($success)
					{
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

						// Finally, delete any caches involving this element. (Even do this for new elements, since they
						// might pop up in a cached criteria.)
						craft()->templateCache->deleteCachesByElement($element);
					}
				}
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the user, in case something changed
			// in onBeforeSaveElement
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			// Specifically look for a MySQL "data truncation" exception. The use-case
			// is for a disabled element where validation doesn't run and a text field
			// is limited in length, but more data is entered than is allowed.
			if (
				$e instanceof \CDbException
				&& isset($e->errorInfo[0])
				&& $e->errorInfo[0] == 22001
				&& isset($e->errorInfo[1])
				&& $e->errorInfo[1] == 1406)
			{
				$success = false;
				craft()->errorHandler->logException($e);
			}
			else
			{
				throw $e;
			}

		}

		if ($success)
		{
			// Fire an 'onSaveElement' event
			$this->onSaveElement(new Event($this, array(
				'element'      => $element,
				'isNewElement' => $isNewElement
			)));
		}
		else
		{
			if ($isNewElement)
			{
				$element->id = null;

				if ($elementType->hasContent())
				{
					$element->getContent()->id = null;
					$element->getContent()->elementId = null;
				}
			}
		}

		if ($success && !$isNewElement)
		{
			// Do any element cleanup work onEndRequest outside of transactions to help with deadlocks.
			$this->_elementCleanup[] = array(
				'localeIds' => $localeIds,
				'elementId' => $element->id,
				'hasContent' => $elementType->hasContent(),
				'contentTable' => $element->getContentTable(),
			);
		}

		return $success;
	}

	/**
	 * Updates an element’s slug and URI, along with any descendants.
	 *
	 * @param BaseElementModel $element            The element to update.
	 * @param bool             $updateOtherLocales Whether the element’s other locales should also be updated.
	 * @param bool             $updateDescendants  Whether the element’s descendants should also be updated.
	 * @param bool             $asTask             Whether the element’s slug and URI should be updated via a background task.
	 *
	 * @return null
	 */
	public function updateElementSlugAndUri(BaseElementModel $element, $updateOtherLocales = true, $updateDescendants = true, $asTask = false)
	{
		if ($asTask)
		{
			craft()->tasks->createTask('UpdateElementSlugsAndUris', null, array(
				'elementId'          => $element->id,
				'elementType'        => $element->getElementType(),
				'locale'             => $element->locale,
				'updateOtherLocales' => $updateOtherLocales,
				'updateDescendants'  => $updateDescendants,
			));

			return;
		}

		ElementHelper::setUniqueUri($element);

		craft()->db->createCommand()->update('elements_i18n', array(
			'slug' => $element->slug,
			'uri'  => $element->uri
		), array(
			'elementId' => $element->id,
			'locale'    => $element->locale
		));

		// Delete any caches involving this element
		craft()->templateCache->deleteCachesByElement($element);

		if ($updateOtherLocales)
		{
			$this->updateElementSlugAndUriInOtherLocales($element);
		}

		if ($updateDescendants)
		{
			$this->updateDescendantSlugsAndUris($element, $updateOtherLocales);
		}
	}

	/**
	 * Updates an element’s slug and URI, for any locales besides the given one.
	 *
	 * @param BaseElementModel $element The element to update.
	 *
	 * @return null
	 */
	public function updateElementSlugAndUriInOtherLocales(BaseElementModel $element)
	{
		foreach (craft()->i18n->getSiteLocaleIds() as $localeId)
		{
			if ($localeId == $element->locale)
			{
				continue;
			}

			$elementInOtherLocale = $this->getElementById($element->id, $element->getElementType(), $localeId);

			if ($elementInOtherLocale)
			{
				$this->updateElementSlugAndUri($elementInOtherLocale, false, false);
			}
		}
	}

	/**
	 * Updates an element’s descendants’ slugs and URIs.
	 *
	 * @param BaseElementModel $element            The element whose descendants should be updated.
	 * @param bool             $updateOtherLocales Whether the element’s other locales should also be updated.
	 * @param bool             $asTask             Whether the descendants’ slugs and URIs should be updated via a background task.
	 *
	 * @return null
	 */
	public function updateDescendantSlugsAndUris(BaseElementModel $element, $updateOtherLocales = true, $asTask = false)
	{
		$criteria = $this->getCriteria($element->getElementType());
		$criteria->descendantOf = $element;
		$criteria->descendantDist = 1;
		$criteria->status = null;
		$criteria->localeEnabled = null;
		$criteria->locale = $element->locale;

		if ($asTask)
		{
			$childIds = $criteria->ids();

			if ($childIds)
			{
				craft()->tasks->createTask('UpdateElementSlugsAndUris', null, array(
					'elementId'          => $childIds,
					'elementType'        => $element->getElementType(),
					'locale'             => $element->locale,
					'updateOtherLocales' => $updateOtherLocales,
					'updateDescendants'  => true,
				));
			}

		}
		else
		{
			$children = $criteria->find();

			foreach ($children as $child)
			{
				$this->updateElementSlugAndUri($child, $updateOtherLocales, true, false);
			}
		}
	}

	/**
	 * Merges two elements together.
	 *
	 * This method will update the following:
	 *
	 * - Any relations involving the merged element
	 * - Any structures that contain the merged element
	 * - Any reference tags in textual custom fields referencing the merged element
	 *
	 * @param int $mergedElementId     The ID of the element that is going away.
	 * @param int $prevailingElementId The ID of the element that is sticking around.
	 *
	 * @throws \Exception
	 * @return bool Whether the elements were merged successfully.
	 */
	public function mergeElementsByIds($mergedElementId, $prevailingElementId)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Update any relations that point to the merged element
			$relations = craft()->db->createCommand()
				->select('id, fieldId, sourceId, sourceLocale')
				->from('relations')
				->where(array('targetId' => $mergedElementId))
				->queryAll();

			foreach ($relations as $relation)
			{
				// Make sure the persisting element isn't already selected in the same field
				$persistingElementIsRelatedToo = (bool) craft()->db->createCommand()
					->from('relations')
					->where(array(
						'fieldId'      => $relation['fieldId'],
						'sourceId'     => $relation['sourceId'],
						'sourceLocale' => $relation['sourceLocale'],
						'targetId'     => $prevailingElementId
					))
					->count('id');

				if (!$persistingElementIsRelatedToo)
				{
					craft()->db->createCommand()->update('relations', array(
						'targetId' => $prevailingElementId
					), array(
						'id' => $relation['id']
					));
				}
			}

			// Update any structures that the merged element is in
			$structureElements = craft()->db->createCommand()
				->select('id, structureId')
				->from('structureelements')
				->where(array('elementId' => $mergedElementId))
				->queryAll();

			foreach ($structureElements as $structureElement)
			{
				// Make sure the persisting element isn't already a part of that structure
				$persistingElementIsInStructureToo = (bool) craft()->db->createCommand()
					->from('structureElements')
					->where(array(
						'structureId' => $structureElement['structureId'],
						'elementId' => $prevailingElementId
					))
					->count('id');

				if (!$persistingElementIsInStructureToo)
				{
					craft()->db->createCommand()->update('relations', array(
						'elementId' => $prevailingElementId
					), array(
						'id' => $structureElement['id']
					));
				}
			}

			// Update any reference tags
			$elementType = $this->getElementTypeById($prevailingElementId);

			if ($elementType)
			{
				$refTagPrefix = '{'.lcfirst($elementType).':';

				craft()->tasks->createTask('FindAndReplace', Craft::t('Updating element references'), array(
					'find'    => $refTagPrefix.$mergedElementId.':',
					'replace' => $refTagPrefix.$prevailingElementId.':',
				));

				craft()->tasks->createTask('FindAndReplace', Craft::t('Updating element references'), array(
					'find'    => $refTagPrefix.$mergedElementId.'}',
					'replace' => $refTagPrefix.$prevailingElementId.'}',
				));
			}

			// Fire an 'onMergeElements' event
			$this->onMergeElements(new Event($this, array(
				'mergedElementId'     => $mergedElementId,
				'prevailingElementId' => $prevailingElementId
			)));

			// Now delete the merged element
			$success = $this->deleteElementById($mergedElementId);

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return $success;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Deletes an element(s) by its ID(s).
	 *
	 * @param int|array $elementIds The element’s ID, or an array of elements’ IDs.
	 *
	 * @throws \Exception
	 * @return bool Whether the element(s) were deleted successfully.
	 */
	public function deleteElementById($elementIds)
	{
		if (!$elementIds)
		{
			return false;
		}

		if (!is_array($elementIds))
		{
			$elementIds = array($elementIds);
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeDeleteElements' event
			$this->onBeforeDeleteElements(new Event($this, array(
				'elementIds' => $elementIds
			)));

			// First delete any structure nodes with these elements, so NestedSetBehavior can do its thing. We need to
			// go one-by-one in case one of theme deletes the record of another in the process.
			foreach ($elementIds as $elementId)
			{
				$records = StructureElementRecord::model()->findAllByAttributes(array(
					'elementId' => $elementId
				));

				foreach ($records as $record)
				{
					// If this element still has any children, move them up before the one getting deleted.
					$children = $record->children()->findAll();

					foreach ($children as $child)
					{
						$child->moveBefore($record);
					}

					// Delete this element's node
					$record->deleteNode();
				}
			}

			// Delete the caches before they drop their elementId relations (passing `false` because there's no chance
			// this element is suddenly going to show up in a new query)
			craft()->templateCache->deleteCachesByElementId($elementIds, false);

			// Now delete the rows in the elements table
			if (count($elementIds) == 1)
			{
				$condition = array('id' => $elementIds[0]);
				$matrixBlockCondition = array('ownerId' => $elementIds[0]);
				$searchIndexCondition = array('elementId' => $elementIds[0]);
			}
			else
			{
				$condition = array('in', 'id', $elementIds);
				$matrixBlockCondition = array('in', 'ownerId', $elementIds);
				$searchIndexCondition = array('in', 'elementId', $elementIds);
			}

			// First delete any Matrix blocks that belong to this element(s)
			$matrixBlockIds = craft()->db->createCommand()
				->select('id')
				->from('matrixblocks')
				->where($matrixBlockCondition)
				->queryColumn();

			if ($matrixBlockIds)
			{
				craft()->matrix->deleteBlockById($matrixBlockIds);
			}

			// Delete the elements table rows, which will cascade across all other InnoDB tables
			$affectedRows = craft()->db->createCommand()->delete('elements', $condition);

			// The searchindex table is MyISAM, though
			craft()->db->createCommand()->delete('searchindex', $searchIndexCondition);

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Deletes elements by a given type.
	 *
	 * @param string $type The element type class handle.
	 *
	 * @return bool Whether the elements were deleted successfully.
	 */
	public function deleteElementsByType($type)
	{
		// Get the IDs and let deleteElementById() take care of the actual deletion
		$elementIds = craft()->db->createCommand()
			->select('id')
			->from('elements')
			->where('type = :type', array(':type' => $type))
			->queryColumn();

		if ($elementIds)
		{
			$this->deleteElementById($elementIds);

			// Delete the template caches
			craft()->templateCache->deleteCachesByElementType($type);
		}
	}

	// Element types
	// -------------------------------------------------------------------------

	/**
	 * Returns all installed element types.
	 *
	 * @return IElementType[] The installed element types.
	 */
	public function getAllElementTypes()
	{
		return craft()->components->getComponentsByType(ComponentType::Element);
	}

	/**
	 * Returns an element type by its class handle.
	 *
	 * @param string $class The element type class handle.
	 *
	 * @return IElementType|null The element type, or `null`.
	 */
	public function getElementType($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::Element, $class);
	}

	// Element Actions
	// -------------------------------------------------------------------------

	/**
	 * Returns all installed element actions.
	 *
	 * @return IElementAction[] The installed element actions.
	 */
	public function getAllActions()
	{
		return craft()->components->getComponentsByType(ComponentType::ElementAction);
	}

	/**
	 * Returns an element action by its class handle.
	 *
	 * @param string $class The element action class handle.
	 *
	 * @return IElementAction|null The element action, or `null`.
	 */
	public function getAction($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::ElementAction, $class);
	}

	// Misc
	// -------------------------------------------------------------------------

	/**
	 * Parses a string for element [reference tags](http://craftcms.com/docs/reference-tags).
	 *
	 * @param string $str The string to parse.
	 *
	 * @return string The parsed string.
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

				if (strpos($matches[1], '_') === false)
				{
					$elementTypeHandle = ucfirst($matches[1]);
				}
				else
				{
					$elementTypeHandle = preg_replace_callback('/^\w|_\w/', function($matches) {
						return strtoupper($matches[0]);
					}, $matches[1]);
				}

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
								$criteria->status = null;
								$criteria->limit = null;
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
												try
												{
													$value = $element->{$refTag['matches'][3]};

													if (is_object($value) && !method_exists($value, '__toString'))
													{
														throw new Exception('Object of class '.get_class($value).' could not be converted to string');
													}

													$replace[] = $this->parseRefs((string)$value);
												}
												catch (\Exception $e)
												{
													// Log it
													Craft::log('An exception was thrown when parsing the ref tag "'.$refTag['matches'][0]."\":\n".$e->getMessage(), LogLevel::Error);

													// Replace the token with the original ref tag
													$replace[] = $refTag['matches'][0];
												}
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

	/**
	 * Stores a placeholder element that {@link findElements()} should use instead of populating a new element with a
	 * matching ID and locale.
	 *
	 * This is used by Live Preview and Sharing features.
	 *
	 * @param BaseElementModel $element The element currently being edited by Live Preview.
	 *
	 * @return null
	 */
	public function setPlaceholderElement(BaseElementModel $element)
	{
		// Won't be able to do anything with this if it doesn't have an ID or locale
		if (!$element->id || !$element->locale)
		{
			return;
		}

		$this->_placeholderElements[$element->id][$element->locale] = $element;
	}

	/**
	 * Perform element clean-up work.
	 */
	public function handleRequestEnd()
	{
		while (($info = array_shift($this->_elementCleanup)) !== null)
		{
			// Delete the rows that don't need to be there anymore
			craft()->db->createCommand()->delete(
				'elements_i18n',
				array('and', 'elementId = :elementId', array('not in', 'locale', $info['localeIds'])),
				array(':elementId' => $info['elementId']));

			if ($info['hasContent'])
			{
				craft()->db->createCommand()->delete(
					$info['contentTable'],
					array('and', 'elementId = :elementId', array('not in', 'locale', $info['localeIds'])),
					array(':elementId' => $info['elementId']));
			}
		}
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeBuildElementsQuery' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeBuildElementsQuery(Event $event)
	{
		$this->raiseEvent('onBeforeBuildElementsQuery', $event);
	}

	/**
	 * Fires an 'onBuildElementsQuery' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBuildElementsQuery(Event $event)
	{
		$this->raiseEvent('onBuildElementsQuery', $event);
	}

	/**
	 * Fires an 'onPopulateElement' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onPopulateElement(Event $event)
	{
		$this->raiseEvent('onPopulateElement', $event);
	}

	/**
	 * Fires an 'onPopulateElements' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onPopulateElements(Event $event)
	{
		$this->raiseEvent('onPopulateElements', $event);
	}

	/**
	 * Fires an 'onMergeElements' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onMergeElements(Event $event)
	{
		$this->raiseEvent('onMergeElements', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteElements' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteElements(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteElements', $event);
	}

	/**
	 * Fires an 'onBeforeSaveElement' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveElement(Event $event)
	{
		$this->raiseEvent('onBeforeSaveElement', $event);
	}

	/**
	 * Fires an 'onSaveElement' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveElement(Event $event)
	{
		$this->raiseEvent('onSaveElement', $event);
	}

	/**
	 * Fires an 'onBeforePerformAction' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforePerformAction(Event $event)
	{
		$this->raiseEvent('onBeforePerformAction', $event);
	}

	/**
	 * Fires an 'onPerformAction' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onPerformAction(Event $event)
	{
		$this->raiseEvent('onPerformAction', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the unique element IDs that match a given element query.
	 *
	 * @param DbCommand $query
	 *
	 * @return array
	 */
	private function _getElementIdsFromQuery(DbCommand $query)
	{
		// Get the matched element IDs, and then have the SearchService filter them.
		$elementIdsQuery = craft()->db->createCommand()
			->select('elements.id')
			->from('elements elements');

		$elementIdsQuery->setWhere($query->getWhere());
		$elementIdsQuery->setJoin($query->getJoin());

		$elementIdsQuery->params = $query->params;
		return $elementIdsQuery->queryColumn();
	}
}
