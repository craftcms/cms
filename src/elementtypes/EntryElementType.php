<?php
namespace Craft;

/**
 * Section element type
 */
class EntryElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Entries');
	}

	/**
	 * Returns whether this element type has titles.
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * Returns whether this element type can have statuses.
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	/**
	 * Returns whether this element type is translatable.
	 *
	 * @return bool
	 */
	public function isTranslatable()
	{
		return true;
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		if ($context == 'index')
		{
			$sections = craft()->sections->getEditableSections();
		}
		else
		{
			$sections = craft()->sections->getAllSections();
		}

		$sectionIds = array();
		$singleSectionIds = array();
		$sectionsByType = array();

		foreach ($sections as $section)
		{
			$sectionIds[] = $section->id;

			if ($section->type == SectionType::Single)
			{
				$singleSectionIds[] = $section->id;
			}
			else
			{
				$sectionsByType[$section->type][] = $section;
			}
		}

		$sources = array(
			'*' => array(
				'label'    => Craft::t('All entries'),
				'criteria' => array('sectionId' => $sectionIds)
			)
		);

		if ($singleSectionIds)
		{
			$sources['singles'] = array(
				'label'    => Craft::t('Singles'),
				'criteria' => array('sectionId' => $singleSectionIds)
			);
		}

		$sectionTypes = array(
			SectionType::Channel => Craft::t('Channels'),
			SectionType::Structure => Craft::t('Structures')
		);

		foreach ($sectionTypes as $type => $heading)
		{
			if (!empty($sectionsByType[$type]))
			{
				$sources[] = array('heading' => $heading);

				foreach ($sectionsByType[$type] as $section)
				{
					$key = 'section:'.$section->id;

					$sources[$key] = array(
						'label'        => $section->name,
						'hasStructure' => ($type == SectionType::Structure),
						'data'         => array('type' => $type),
						'criteria'     => array('sectionId' => $section->id)
					);

					if ($type == SectionType::Structure)
					{
						$sources[$key]['hasStructure'] = true;
						$sources[$key]['sortable'] = craft()->userSession->checkPermission('publishEntries:'.$section->id);
						$sources[$key]['moveAction'] = 'entries/moveEntry';
						$sources[$key]['maxDepth'] = $section->maxDepth;
						$sources[$key]['newChildUrl'] = 'entries/'.$section->handle.'/new';
					}
				}
			}
		}

		return $sources;
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('slug');
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		$attributes = array();

		if ($source && preg_match('/^section:(\d+)$/', $source, $match))
		{
			$section = craft()->sections->getSectionById($match[1]);
		}

		$attributes = array(
			array('label' => Craft::t('Title'), 'attribute' => 'title'),
		);

		if ($source != 'singles')
		{
			$attributes[] = array('label' => Craft::t('Slug'), 'attribute' => 'slug', 'link' => true);

			if (empty($section))
			{
				$attributes[] = array('label' => Craft::t('Section'), 'attribute' => 'sectionId', 'display' => '{section}');
			}

			$attributes[] = array('label' => Craft::t('Post Date'), 'attribute' => 'postDate', 'display' => '{postDate.localeDate}');
			$attributes[] = array('label' => Craft::t('Expiry Date'), 'attribute' => 'expiryDate', 'display' => '{expiryDate.localeDate}');
		}

		return $attributes;
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'type'            => AttributeType::Mixed,
			'slug'            => AttributeType::String,
			'sectionId'       => AttributeType::Number,
			'authorId'        => AttributeType::Number,
			'authorGroupId'   => AttributeType::Number,
			'authorGroup'     => AttributeType::String,
			'section'         => AttributeType::Mixed,
			'editable'        => AttributeType::Bool,
			'postDate'        => AttributeType::Mixed,
			'after'           => AttributeType::Mixed,
			'before'          => AttributeType::Mixed,
			'status'          => array(AttributeType::String, 'default' => EntryModel::LIVE),
			'order'           => array(AttributeType::String, 'default' => 'lft, postDate desc'),
			'ancestorOf'      => AttributeType::Mixed,
			'ancestorDist'    => AttributeType::Number,
			'descendantOf'    => AttributeType::Mixed,
			'descendantDist'  => AttributeType::Number,
			'prevSiblingOf'   => AttributeType::Mixed,
			'nextSiblingOf'   => AttributeType::Mixed,
			'depth'           => AttributeType::Number,
		);
	}

	/**
	 * Returns the element query condition for a custom status criteria.
	 *
	 * @param DbCommand $query
	 * @param string $status
	 * @return string|false
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
		$currentTimeDb = DateTimeHelper::currentTimeForDb();

		switch ($status)
		{
			case EntryModel::LIVE:
			{
				return array('and',
					'elements.enabled = 1',
					"entries.postDate <= '{$currentTimeDb}'",
					array('or', 'entries.expiryDate is null', "entries.expiryDate > '{$currentTimeDb}'")
				);
			}

			case EntryModel::PENDING:
			{
				return array('and',
					'elements.enabled = 1',
					"entries.postDate > '{$currentTimeDb}'"
				);
			}

			case EntryModel::EXPIRED:
			{
				return array('and',
					'elements.enabled = 1',
					'entries.expiryDate is not null',
					"entries.expiryDate <= '{$currentTimeDb}'"
				);
			}
		}
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('entries.sectionId, entries.typeId, entries.authorId, entries.root, entries.lft, entries.rgt, entries.depth, entries.postDate, entries.expiryDate, entries_i18n.slug')
			->join('entries entries', 'entries.id = elements.id')
			->join('entries_i18n entries_i18n', 'entries_i18n.entryId = elements.id')
			->andWhere(array('or', 'entries.lft IS NULL', 'entries.lft != 1'))
			->andWhere('entries_i18n.locale = elements_i18n.locale');

		$joinedSections = false;

		if ($criteria->ref)
		{
			$refs = ArrayHelper::stringToArray($criteria->ref);
			$conditionals = array();

			foreach ($refs as $ref)
			{
				$parts = array_filter(explode('/', $ref));

				if ($parts)
				{
					if (count($parts) == 1)
					{
						$conditionals[] = DbHelper::parseParam('entries_i18n.slug', $parts[0], $query->params);
					}
					else
					{
						if (!$joinedSections)
						{
							$query->join('sections sections', 'entries.sectionId = sections.id');
							$joinedSections = true;
						}

						$conditionals[] = array('and',
							DbHelper::parseParam('sections.handle', $parts[0], $query->params),
							DbHelper::parseParam('entries_i18n.slug', $parts[1], $query->params)
						);
					}
				}
			}

			if ($conditionals)
			{
				if (count($conditionals) == 1)
				{
					$query->andWhere($conditionals[0]);
				}
				else
				{
					array_unshift($conditionals, 'or');
					$query->andWhere($conditionals);
				}
			}
		}

		if ($criteria->type)
		{
			$typeIds = array();

			if (!is_array($criteria->type))
			{
				$criteria->type = array($criteria->type);
			}

			foreach ($criteria->type as $type)
			{
				if (is_numeric($type))
				{
					$typeIds[] = $type;
				}
				else if (is_string($type))
				{
					$types = craft()->sections->getEntryTypesByHandle($type);

					if ($types)
					{
						foreach ($types as $type)
						{
							$typeIds[] = $type->id;
						}
					}
					else
					{
						return false;
					}
				}
				else if ($type instanceof EntryTypeModel)
				{
					$typeIds[] = $type->id;
				}
				else
				{
					return false;
				}
			}

			$query->andWhere(DbHelper::parseParam('entries.typeId', $typeIds, $query->params));
		}

		if ($criteria->slug)
		{
			$query->andWhere(DbHelper::parseParam('entries_i18n.slug', $criteria->slug, $query->params));
		}

		if ($criteria->postDate)
		{
			$query->andWhere(DbHelper::parseDateParam('entries.postDate', '=', $criteria->postDate, $query->params));
		}
		else
		{
			if ($criteria->after)
			{
				$query->andWhere(DbHelper::parseDateParam('entries.postDate', '>=', $criteria->after, $query->params));
			}

			if ($criteria->before)
			{
				$query->andWhere(DbHelper::parseDateParam('entries.postDate', '<', $criteria->before, $query->params));
			}
		}

		if ($criteria->editable)
		{
			$user = craft()->userSession->getUser();

			if (!$user)
			{
				return false;
			}

			$editableSectionIds = craft()->sections->getEditableSectionIds();
			$query->andWhere(array('in', 'entries.sectionId', $editableSectionIds));

			$noPeerConditions = array();

			foreach ($editableSectionIds as $sectionId)
			{
				if (!$user->can('editPeerEntries:'.$sectionId))
				{
					$noPeerConditions[] = array('or', 'entries.sectionId != '.$sectionId, 'entries.authorId = '.$user->id);
				}
			}

			if ($noPeerConditions)
			{
				array_unshift($noPeerConditions, 'and');
				$query->andWhere($noPeerConditions);
			}
		}

		if ($criteria->sectionId)
		{
			$query->andWhere(DbHelper::parseParam('entries.sectionId', $criteria->sectionId, $query->params));
		}

		if ($criteria->section)
		{
			if (!$joinedSections)
			{
				$query->join('sections sections', 'entries.sectionId = sections.id');
				$joinedSections = true;
			}

			$query->andWhere(DbHelper::parseParam('sections.handle', $criteria->section, $query->params));
		}

		if (Craft::hasPackage(CraftPackage::PublishPro))
		{
			if ($criteria->ancestorOf)
			{
				if (!$criteria->ancestorOf instanceof EntryModel)
				{
					$criteria->ancestorOf = craft()->entries->getEntryById($criteria->ancestorOf);
				}

				if ($criteria->ancestorOf)
				{
					$query->andWhere(
						array('and',
							'entries.lft < :ancestorOf_lft',
							'entries.rgt > :ancestorOf_rgt',
							'entries.sectionId = :ancestorOf_sectionId'
						),
						array(
							':ancestorOf_lft'       => $criteria->ancestorOf->lft,
							':ancestorOf_rgt'       => $criteria->ancestorOf->rgt,
							':ancestorOf_sectionId' => $criteria->ancestorOf->sectionId
						)
					);

					if ($criteria->ancestorDist)
					{
						$query->andWhere('entries.depth >= :depth',
							array(':depth' => $criteria->ancestorOf->depth - $criteria->ancestorDist)
						);
					}
				}
			}

			if ($criteria->descendantOf)
			{
				if (!$criteria->descendantOf instanceof EntryModel)
				{
					$criteria->descendantOf = craft()->entries->getEntryById($criteria->descendantOf);
				}

				if ($criteria->descendantOf)
				{
					$query->andWhere(
						array('and',
							'entries.lft > :descendantOf_lft',
							'entries.rgt < :descendantOf_rgt',
							'entries.sectionId = :descendantOf_sectionId'
						),
						array(
							':descendantOf_lft'       => $criteria->descendantOf->lft,
							':descendantOf_rgt'       => $criteria->descendantOf->rgt,
							':descendantOf_sectionId' => $criteria->descendantOf->sectionId
						)
					);

					if ($criteria->descendantDist)
					{
						$query->andWhere('entries.depth <= :depth',
							array(':depth' => $criteria->descendantOf->depth + $criteria->descendantDist)
						);
					}
				}
			}

			if ($criteria->prevSiblingOf)
			{
				if (!$criteria->prevSiblingOf instanceof EntryModel)
				{
					$criteria->prevSiblingOf = craft()->entries->getEntryById($criteria->prevSiblingOf);
				}

				if ($criteria->prevSiblingOf)
				{
					$query->andWhere(
						array('and',
							'entries.rgt = :prevSiblingOf_rgt',
							'entries.sectionId = :prevSiblingOf_sectionId'
						),
						array(
							':prevSiblingOf_rgt'       => $criteria->prevSiblingOf->lft - 1,
							':prevSiblingOf_sectionId' => $criteria->prevSiblingOf->sectionId
						)
					);
				}
			}

			if ($criteria->nextSiblingOf)
			{
				if (!$criteria->nextSiblingOf instanceof EntryModel)
				{
					$criteria->nextSiblingOf = craft()->entries->getEntryById($criteria->nextSiblingOf);
				}

				if ($criteria->nextSiblingOf)
				{
					$query->andWhere(
						array('and',
							'entries.lft = :nextSiblingOf_lft',
							'entries.sectionId = :nextSiblingOf_sectionId'
						),
						array(
							':nextSiblingOf_lft'       => $criteria->nextSiblingOf->rgt + 1,
							':nextSiblingOf_sectionId' => $criteria->nextSiblingOf->sectionId
						)
					);
				}
			}

			if ($criteria->depth)
			{
				$query->andWhere(DbHelper::parseParam('entries.depth', $criteria->depth, $query->params));
			}
		}

		if (Craft::hasPackage(CraftPackage::Users))
		{
			if ($criteria->authorId)
			{
				$query->andWhere(DbHelper::parseParam('entries.authorId', $criteria->authorId, $query->params));
			}

			if ($criteria->authorGroupId || $criteria->authorGroup)
			{
				$query->join('usergroups_users usergroups_users', 'usergroups_users.userId = entries.authorId');

				if ($criteria->authorGroupId)
				{
					$query->andWhere(DbHelper::parseParam('usergroups_users.groupId', $criteria->authorGroupId, $query->params));
				}

				if ($criteria->authorGroup)
				{
					$query->join('usergroups usergroups', 'usergroups.id = usergroups_users.groupId');
					$query->andWhere(DbHelper::parseParam('usergroups.handle', $criteria->authorGroup, $query->params));
				}
			}
		}
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return EntryModel::populateModel($row);
	}

	/**
	 * Routes the request when the URI matches an element.
	 *
	 * @param BaseElementModel
	 * @return mixed Can be false if no special action should be taken,
	 *               a string if it should route to a template path,
	 *               or an array that can specify a controller action path, params, etc.
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		// Make sure that the entry is actually live
		if ($element->getStatus() == EntryModel::LIVE)
		{
			$section = $element->getSection();

			// Make sure the section is set to have URLs and is enabled for this locale
			if ($section->hasUrls && array_key_exists(craft()->language, $section->getLocales()))
			{
				return array(
					'action' => 'templates/render',
					'params' => array(
						'template' => $section->template,
						'variables' => array(
							'entry' => $element
						)
					)
				);
			}
		}

		return false;
	}
}
