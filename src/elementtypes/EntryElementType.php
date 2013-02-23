<?php
namespace Blocks;

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
		return Blocks::t('Section Entries');
	}

	/**
	 * Returns the CP edit URI for a given element.
	 *
	 * @param ElementModel $element
	 * @return string|null
	 */
	public function getCpEditUriForElement(ElementModel $element)
	{
		return 'content/'.$element->getSection()->handle.'/'.$element->id;
	}

	/**
	 * Returns the site template path for a matched element.
	 *
	 * @param ElementModel
	 * @return string|false
	 */
	public function getSiteTemplateForMatchedElement(ElementModel $element)
	{
		// Make sure that the entry is actually live
		if ($element->getStatus() == EntryModel::LIVE)
		{
			$section = $element->getSection();

			// Make sure the section is set to have URLs and is enabled for this locale
			if ($section->hasUrls && array_key_exists(blx()->language, $section->getLocales()))
			{
				return $section->template;
			}
		}

		return false;
	}

	/**
	 * Returns the variable name the matched element should be assigned to.
	 *
	 * @return string
	 */
	public function getVariableNameForMatchedElement()
	{
		return 'entry';
	}

	/**
	 * Returns whether this element type is localizable.
	 *
	 * @return bool
	 */
	public function isLocalizable()
	{
		return true;
	}

	/**
	 * Returns whether this element type is linkable.
	 *
	 * @return bool
	 */
	public function isLinkable()
	{
		return true;
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCustomCriteriaAttributes()
	{
		return array(
			//'title'         => AttributeType::String,
			'slug'          => AttributeType::String,
			'sectionId'     => AttributeType::Number,
			'authorId'      => AttributeType::Number,
			'authorGroupId' => AttributeType::Number,
			'authorGroup'   => AttributeType::String,
			'section'       => AttributeType::Mixed,
			'editable'      => AttributeType::Bool,
			'after'         => AttributeType::DateTime,
			'before'        => AttributeType::DateTime,
		);
	}

	/**
	 * Returns the link settings HTML
	 *
	 * @return string|null
	 */
	public function getLinkSettingsHtml()
	{
		return blx()->templates->render('_components/elementtypes/Entry/linksettings', array(
			'settings' => $this->getLinkSettings()
		));
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
			->addSelect('entries.sectionId, entries.authorId, entries.postDate, entries.expiryDate, entries_i18n.title, entries_i18n.slug')
			->join('entries entries', 'entries.id = elements.id')
			->join('entries_i18n entries_i18n', 'entries_i18n.entryId = elements.id')
			->andWhere('entries_i18n.locale = elements_i18n.locale');

		if ($criteria->slug)
		{
			$query->andWhere(DbHelper::parseParam('entries_i18n.slug', $criteria->slug, $query->params));
		}

		if ($criteria->after)
		{
			$query->addWhere(DbHelper::parseDateParam('elements.postDate', '>=', $criteria->after, $query->params));
		}

		if ($criteria->before)
		{
			$query->addWhere(DbHelper::parseDateParam('elements.postDate', '<', $criteria->before, $query->params));
		}

		if ($criteria->status)
		{
			$statusCondition = $this->_getEntryStatusCondition($criteria->status);

			if ($statusCondition)
			{
				$query->addWhere($statusCondition);
			}
		}

		if ($criteria->editable)
		{
			$user = blx()->userSession->getUser();

			if (!$user)
			{
				return false;
			}

			$editableSectionIds = blx()->sections->getEditableSectionIds();
			$query->andWhere(array('in', 'entries.sectionId', $editableSectionIds));

			$noPeerConditions = array();

			foreach ($editableSectionIds as $sectionId)
			{
				if (!$user->can('editPeerEntriesInSection'.$sectionId))
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

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
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

		if (Blocks::hasPackage(BlocksPackage::Users))
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
	 * Returns the element status conditions.
	 *
	 * @access private
	 * @param $statusParam
	 * @return array
	 */
	private function _getEntryStatusCondition($statusParam)
	{
		$statusConditions = array();
		$statuses = ArrayHelper::stringToArray($statusParam);

		foreach ($statuses as $status)
		{
			$status = strtolower($status);
			$currentTimeDb = DateTimeHelper::currentTimeForDb();

			switch ($status)
			{
				case 'live':
				{
					$statusConditions[] = array('and',
						'elements.enabled = 1',
						"elements.postDate <= '{$currentTimeDb}'",
						array('or', 'elements.expiryDate is null', "elements.expiryDate > '{$currentTimeDb}'")
					);
					break;
				}
				case 'pending':
				{
					$statusConditions[] = array('and',
						'elements.enabled = 1',
						"elements.postDate > '{$currentTimeDb}'"
					);
					break;
				}
				case 'expired':
				{
					$statusConditions[] = array('and',
						'elements.enabled = 1',
						'elements.expiryDate is not null',
						"elements.expiryDate <= '{$currentTimeDb}'"
					);
					break;
				}
				case 'disabled':
				{
					$statusConditions[] = 'elements.enabled != 1';
				}
			}
		}

		if ($statusConditions)
		{
			if (count($statusConditions) == 1)
			{
				return $statusConditions[0];
			}
			else
			{
				array_unshift($conditions, 'or');
				return $statusConditions;
			}
		}
	}
}
