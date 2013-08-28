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

		foreach ($sections as $section)
		{
			$sectionIds[] = $section->id;
		}

		$sources = array(
			'*' => array(
				'label'    => Craft::t('All entries'),
				'criteria' => array('sectionId' => $sectionIds)
			)
		);

		if (Craft::hasPackage(CraftPackage::PublishPro))
		{
			foreach ($sections as $section)
			{
				$key = 'section:'.$section->id;

				$sources[$key] = array(
					'label'    => $section->name,
					'criteria' => array('sectionId' => $section->id)
				);
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

		if (Craft::hasPackage(CraftPackage::PublishPro))
		{
			if ($source && preg_match('/^section:(\d+)$/', $source, $match))
			{
				$section = craft()->sections->getSectionById($match[1]);
			}
		}
		else if (!$source)
		{
			$sections = craft()->sections->getAllSections();

			if ($sections)
			{
				$section = $sections[0];
			}
		}

		$attributes = array(
			array('label' => Craft::t('Title'), 'attribute' => 'title'),
			array('label' => Craft::t('Slug'),  'attribute' => 'slug', 'link' => true),
		);

		if (empty($section))
		{
			$attributes[] = array('label' => Craft::t('Section'), 'attribute' => 'sectionId', 'display' => '{section}');
		}

		$attributes[] = array('label' => Craft::t('Post Date'), 'attribute' => 'postDate', 'display' => '{postDate.localeDate}');
		$attributes[] = array('label' => Craft::t('Expiry Date'), 'attribute' => 'expiryDate', 'display' => '{expiryDate.localeDate}');

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
			'slug'          => AttributeType::String,
			'sectionId'     => AttributeType::Number,
			'authorId'      => AttributeType::Number,
			'authorGroupId' => AttributeType::Number,
			'authorGroup'   => AttributeType::String,
			'section'       => AttributeType::Mixed,
			'editable'      => AttributeType::Bool,
			'postDate'      => AttributeType::Mixed,
			'after'         => AttributeType::Mixed,
			'before'        => AttributeType::Mixed,
			'status'        => array(AttributeType::String, 'default' => EntryModel::LIVE),
			'order'         => array(AttributeType::String, 'default' => 'postDate desc'),
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
			->addSelect('entries.sectionId, entries.authorId, entries.postDate, entries.expiryDate, entries_i18n.slug')
			->join('entries entries', 'entries.id = elements.id')
			->join('entries_i18n entries_i18n', 'entries_i18n.entryId = elements.id')
			->andWhere('entries_i18n.locale = elements_i18n.locale');

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

		if (Craft::hasPackage(CraftPackage::PublishPro))
		{
			if ($criteria->sectionId)
			{
				$query->andWhere(DbHelper::parseParam('entries.sectionId', $criteria->sectionId, $query->params));
			}

			if ($criteria->section)
			{
				$query->join('sections sections', 'entries.sectionId = sections.id');
				$query->andWhere(DbHelper::parseParam('sections.handle', $criteria->section, $query->params));
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
