<?php
namespace Craft;

/**
 * The EntryElementType class is responsible for implementing and defining entries as a native element type in Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.elementtypes
 * @since     1.0
 */
class EntryElementType extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Entries');
	}

	/**
	 * @inheritDoc IElementType::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::hasTitles()
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::isLocalized()
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::getStatuses()
	 *
	 * @return array|null
	 */
	public function getStatuses()
	{
		return array(
			EntryModel::LIVE => Craft::t('Live'),
			EntryModel::PENDING => Craft::t('Pending'),
			EntryModel::EXPIRED => Craft::t('Expired'),
			BaseElementModel::DISABLED => Craft::t('Disabled')
		);
	}

	/**
	 * @inheritDoc IElementType::getSources()
	 *
	 * @param null $context
	 *
	 * @return array|bool|false
	 */
	public function getSources($context = null)
	{
		if ($context == 'index')
		{
			$sections = craft()->sections->getEditableSections();
			$editable = true;
		}
		else
		{
			$sections = craft()->sections->getAllSections();
			$editable = false;
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
				'criteria' => array('sectionId' => $sectionIds, 'editable' => $editable)
			)
		);

		if ($singleSectionIds)
		{
			$sources['singles'] = array(
				'label'    => Craft::t('Singles'),
				'criteria' => array('sectionId' => $singleSectionIds, 'editable' => $editable)
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
						'label'    => Craft::t($section->name),
						'data'     => array('type' => $type, 'handle' => $section->handle),
						'criteria' => array('sectionId' => $section->id, 'editable' => $editable)
					);

					if ($type == SectionType::Structure)
					{
						$sources[$key]['structureId'] = $section->structureId;
						$sources[$key]['newChildUrl'] = 'entries/'.$section->handle.'/new';
					}
				}
			}
		}

		return $sources;
	}

	/**
	 * @inheritDoc IElementType::defineTableAttributes()
	 *
	 * @param null $source
	 *
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		if ($source && preg_match('/^section:(\d+)$/', $source, $match))
		{
			$section = craft()->sections->getSectionById($match[1]);
		}

		$attributes = array(
			'title' => Craft::t('Title'),
			'uri'   => Craft::t('URI'),
		);

		if ($source != 'singles')
		{
			if (empty($section))
			{
				$attributes['sectionId'] = Craft::t('Section');
			}

			$attributes['postDate']   = Craft::t('Post Date');
			$attributes['expiryDate'] = Craft::t('Expiry Date');
		}

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return mixed|null|string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		switch ($attribute)
		{
			case 'sectionId':
			{
				return Craft::t($element->getSection()->name);
			}

			case 'postDate':
			case 'expiryDate':
			{
				$date = $element->$attribute;

				if ($date)
				{
					return $date->localeDate();
				}
				else
				{
					return '';
				}
			}

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * @inheritDoc IElementType::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'after'           => AttributeType::Mixed,
			'authorGroup'     => AttributeType::String,
			'authorGroupId'   => AttributeType::Number,
			'authorId'        => AttributeType::Number,
			'before'          => AttributeType::Mixed,
			'editable'        => AttributeType::Bool,
			'order'           => array(AttributeType::String, 'default' => 'lft, postDate desc'),
			'postDate'        => AttributeType::Mixed,
			'section'         => AttributeType::Mixed,
			'sectionId'       => AttributeType::Number,
			'status'          => array(AttributeType::String, 'default' => EntryModel::LIVE),
			'type'            => AttributeType::Mixed,
		);
	}

	/**
	 * @inheritDoc IElementType::getElementQueryStatusCondition()
	 *
	 * @param DbCommand $query
	 * @param string    $status
	 *
	 * @return array|false|string|void
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
					'elements_i18n.enabled = 1',
					"entries.postDate <= '{$currentTimeDb}'",
					array('or', 'entries.expiryDate is null', "entries.expiryDate > '{$currentTimeDb}'")
				);
			}

			case EntryModel::PENDING:
			{
				return array('and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					"entries.postDate > '{$currentTimeDb}'"
				);
			}

			case EntryModel::EXPIRED:
			{
				return array('and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					'entries.expiryDate is not null',
					"entries.expiryDate <= '{$currentTimeDb}'"
				);
			}
		}
	}

	/**
	 * @inheritDoc IElementType::modifyElementsQuery()
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool|false|null|void
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('entries.sectionId, entries.typeId, entries.authorId, entries.postDate, entries.expiryDate')
			->join('entries entries', 'entries.id = elements.id')
			->join('sections sections', 'sections.id = entries.sectionId')
			->leftJoin('structures structures', 'structures.id = sections.structureId')
			->leftJoin('structureelements structureelements', array('and', 'structureelements.structureId = structures.id', 'structureelements.elementId = entries.id'));

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
						$conditionals[] = DbHelper::parseParam('elements_i18n.slug', $parts[0], $query->params);
					}
					else
					{
						$conditionals[] = array('and',
							DbHelper::parseParam('sections.handle', $parts[0], $query->params),
							DbHelper::parseParam('elements_i18n.slug', $parts[1], $query->params)
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

		if ($criteria->postDate)
		{
			$query->andWhere(DbHelper::parseDateParam('entries.postDate', $criteria->postDate, $query->params));
		}
		else
		{
			if ($criteria->after)
			{
				$query->andWhere(DbHelper::parseDateParam('entries.postDate', '>='.$criteria->after, $query->params));
			}

			if ($criteria->before)
			{
				$query->andWhere(DbHelper::parseDateParam('entries.postDate', '<'.$criteria->before, $query->params));
			}
		}

		if ($criteria->editable)
		{
			$user = craft()->userSession->getUser();

			if (!$user)
			{
				return false;
			}

			// Limit the query to only the sections the user has permission to edit
			$editableSectionIds = craft()->sections->getEditableSectionIds();
			$query->andWhere(array('in', 'entries.sectionId', $editableSectionIds));

			// Enforce the editPeerEntries permissions for non-Single sections
			$noPeerConditions = array();

			foreach (craft()->sections->getEditableSections() as $section)
			{
				if (
					$section->type != SectionType::Single &&
					!$user->can('editPeerEntries:'.$section->id)
				)
				{
					$noPeerConditions[] = array('or', 'entries.sectionId != '.$section->id, 'entries.authorId = '.$user->id);
				}
			}

			if ($noPeerConditions)
			{
				array_unshift($noPeerConditions, 'and');
				$query->andWhere($noPeerConditions);
			}
		}

		if ($criteria->section)
		{
			if ($criteria->section instanceof SectionModel)
			{
				$criteria->sectionId = $criteria->section->id;
				$criteria->section = null;
			}
			else
			{
				$query->andWhere(DbHelper::parseParam('sections.handle', $criteria->section, $query->params));
			}
		}

		if ($criteria->sectionId)
		{
			$query->andWhere(DbHelper::parseParam('entries.sectionId', $criteria->sectionId, $query->params));
		}

		if (craft()->getEdition() >= Craft::Client)
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
	 * @inheritDoc IElementType::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return BaseElementModel|BaseModel|void
	 */
	public function populateElementModel($row)
	{
		return EntryModel::populateModel($row);
	}

	/**
	 * @inheritDoc IElementType::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		if ($element->getType()->hasTitleField)
		{
			$html = craft()->templates->render('entries/_titlefield', array(
				'entry' => $element
			));
		}
		else
		{
			$html = '';
		}

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * @inheritdoc BaseElementType::saveElement()
	 *
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params)
	{
		// Route this through EntriesService::saveEntry() so the proper entry events get fired.
		return craft()->entries->saveEntry($element);
	}

	/**
	 * Routes the request when the URI matches an element.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return array|bool|mixed
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

	/**
	 * @inheritDoc IElementType::onAfterMoveElementInStructure()
	 *
	 * @param BaseElementModel $element
	 * @param int              $structureId
	 *
	 * @return null|void
	 */
	public function onAfterMoveElementInStructure(BaseElementModel $element, $structureId)
	{
		// Was the entry moved within its section's structure?
		$section = $element->getSection();

		if ($section->type == SectionType::Structure && $section->structureId == $structureId)
		{
			craft()->elements->updateElementSlugAndUri($element);
		}
	}
}
