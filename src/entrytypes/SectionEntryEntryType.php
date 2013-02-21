<?php
namespace Blocks;

/**
 * Section entry type
 */
class SectionEntryEntryType extends BaseEntryType
{
	/**
	 * Returns the entry type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Section Entries');
	}

	/**
	 * Returns the CP edit URI for a given entry.
	 *
	 * @param EntryModel $entry
	 * @return string|null
	 */
	public function getCpEditUriForEntry(EntryModel $entry)
	{
		return 'content/'.$entry->getSection()->handle.'/'.$entry->id;
	}

	/**
	 * Returns the site template path for a matched entry.
	 *
	 * @param SectionEntryModel
	 * @return string|false
	 */
	public function getSiteTemplateForMatchedEntry(SectionEntryModel $entry)
	{
		$section = $entry->getSection();

		if ($section->hasUrls)
		{
			return $section->template;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns whether this entry type is localizable.
	 *
	 * @return bool
	 */
	public function isLocalizable()
	{
		return true;
	}

	/**
	 * Returns whether this entry type is linkable.
	 *
	 * @return bool
	 */
	public function isLinkable()
	{
		return true;
	}

	/**
	 * Defines any custom entry criteria attributes for this entry type.
	 *
	 * @return array
	 */
	public function defineCustomCriteriaAttributes()
	{
		return array(
			'slug'          => AttributeType::String,
			'sectionId'     => AttributeType::Number,
			'authorId'      => AttributeType::Number,
			'authorGroupId' => AttributeType::Number,
			'authorGroup'   => AttributeType::String,
			'section'       => AttributeType::Mixed,
			'editable'      => AttributeType::Bool,
		);
	}

	/**
	 * Returns the link settings HTML
	 *
	 * @return string|null
	 */
	public function getLinkSettingsHtml()
	{
		return blx()->templates->render('_components/entrytypes/SectionEntry/linksettings', array(
			'settings' => $this->getLinkSettings()
		));
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param EntryCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyEntriesQuery(DbCommand $query, EntryCriteriaModel $criteria)
	{
		$query
			->addSelect('se.sectionId, se.authorId, se_i18n.slug')
			->join('sectionentries se', 'se.id = e.id')
			->join('sectionentries_i18n se_i18n', 'se_i18n.entryId = e.id')
			->andWhere('se_i18n.locale = e_i18n.locale');

		if ($criteria->slug)
		{
			$query->andWhere(DbHelper::parseParam('se_i18n.slug', $criteria->slug, $query->params));
		}

		if ($criteria->editable)
		{
			$user = blx()->userSession->getUser();

			if (!$user)
			{
				return false;
			}

			$editableSectionIds = blx()->sections->getEditableSectionIds();
			$query->andWhere(array('in', 'se.sectionId', $editableSectionIds));

			$noPeerConditions = array();

			foreach ($editableSectionIds as $sectionId)
			{
				if (!$user->can('editPeerEntriesInSection'.$sectionId))
				{
					$noPeerConditions[] = array('or', 'se.sectionId != '.$sectionId, 'se.authorId = '.$user->id);
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
				$query->andWhere(DbHelper::parseParam('se.sectionId', $criteria->sectionId, $query->params));
			}

			if ($criteria->section)
			{
				$query->join('sections s', 'se.sectionId = s.id');
				$query->andWhere(DbHelper::parseParam('s.handle', $criteria->section, $query->params));
			}
		}

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			if ($criteria->authorId)
			{
				$query->andWhere(DbHelper::parseParam('se.authorId', $criteria->authorId, $query->params));
			}

			if ($criteria->authorGroupId || $criteria->authorGroup)
			{
				$query->join('usergroups_users ugu', 'ugu.userId = se.authorId');

				if ($criteria->authorGroupId)
				{
					$query->andWhere(DbHelper::parseParam('ugu.groupId', $criteria->authorGroupId, $query->params));
				}

				if ($criteria->authorGroup)
				{
					$query->join('usergroups ug', 'ug.id = ugu.groupId');
					$query->andWhere(DbHelper::parseParam('ug.handle', $criteria->authorGroup, $query->params));
				}
			}
		}
	}

	/**
	 * Populates an entry model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateEntryModel($row)
	{
		return SectionEntryModel::populateModel($row);
	}
}
