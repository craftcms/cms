<?php
namespace Craft;

/**
 * The EntryElementType class is responsible for implementing and defining entries as a native element type in Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
				'label'       => Craft::t('All entries'),
				'criteria'    => array('sectionId' => $sectionIds, 'editable' => $editable),
				'defaultSort' => array('postDate', 'desc')
			)
		);

		if ($singleSectionIds)
		{
			$sources['singles'] = array(
				'label'       => Craft::t('Singles'),
				'criteria'    => array('sectionId' => $singleSectionIds, 'editable' => $editable),
				'defaultSort' => array('title', 'asc')
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
						'label'    => HtmlHelper::encode(Craft::t($section->name)),
						'data'     => array('type' => $type, 'handle' => $section->handle),
						'criteria' => array('sectionId' => $section->id, 'editable' => $editable)
					);

					if ($type == SectionType::Structure)
					{
						$sources[$key]['defaultSort'] = array('structure', 'asc');
						$sources[$key]['structureId'] = $section->structureId;
						$sources[$key]['structureEditable'] = craft()->userSession->checkPermission('publishEntries:'.$section->id);
					}
					else
					{
						$sources[$key]['defaultSort'] = array('postDate', 'desc');
					}
				}
			}
		}

		// Allow plugins to modify the sources
		craft()->plugins->call('modifyEntrySources', array(&$sources, $context));

		return $sources;
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		// Get the section(s) we need to check permissions on
		switch ($source)
		{
			case '*':
			{
				$sections = craft()->sections->getEditableSections();
				break;
			}
			case 'singles':
			{
				$sections = craft()->sections->getSectionsByType(SectionType::Single);
				break;
			}
			default:
			{
				if (preg_match('/^section:(\d+)$/', $source, $matches))
				{
					$section = craft()->sections->getSectionById($matches[1]);

					if ($section)
					{
						$sections = array($section);
					}
				}
			}
		}

		// Now figure out what we can do with these
		$actions = array();

		if (!empty($sections))
		{
			$userSessionService = craft()->userSession;
			$canSetStatus = true;
			$canEdit = false;

			foreach ($sections as $section)
			{
				$canPublishEntries = $userSessionService->checkPermission('publishEntries:'.$section->id);

				// Only show the Set Status action if we're sure they can make changes in all the sections
				if (!(
					$canPublishEntries &&
					($section->type == SectionType::Single || $userSessionService->checkPermission('publishPeerEntries:'.$section->id))
				))
				{
					$canSetStatus = false;
				}

				// Show the Edit action if they can publish changes to *any* of the sections
				// (the trigger will disable itself for entries that aren't editable)
				if ($canPublishEntries)
				{
					$canEdit = true;
				}
			}

			// Set Status
			if ($canSetStatus)
			{
				$setStatusAction = craft()->elements->getAction('SetStatus');
				$setStatusAction->onSetStatus = function(Event $event)
				{
					if ($event->params['status'] == BaseElementModel::ENABLED)
					{
						// Set a Post Date as well
						craft()->db->createCommand()->update(
							'entries',
							array('postDate' => DateTimeHelper::currentTimeForDb()),
							array('and', array('in', 'id', $event->params['elementIds']), 'postDate is null')
						);
					}
				};
				$actions[] = $setStatusAction;
			}

			// Edit
			if ($canEdit)
			{
				$editAction = craft()->elements->getAction('Edit');
				$editAction->setParams(array(
					'label' => Craft::t('Edit entry'),
				));
				$actions[] = $editAction;
			}

			if ($source == '*' || $source == 'singles' || $sections[0]->hasUrls)
			{
				// View
				$viewAction = craft()->elements->getAction('View');
				$viewAction->setParams(array(
					'label' => Craft::t('View entry'),
				));
				$actions[] = $viewAction;
			}

			// Channel/Structure-only actions
			if ($source != '*' && $source != 'singles')
			{
				$section = $sections[0];

				// New child?
				if (
					$section->type == SectionType::Structure &&
					$userSessionService->checkPermission('createEntries:'.$section->id)
				)
				{
					$structure = craft()->structures->getStructureById($section->structureId);

					if ($structure)
					{
						$newChildAction = craft()->elements->getAction('NewChild');
						$newChildAction->setParams(array(
							'label'       => Craft::t('Create a new child entry'),
							'maxLevels'   => $structure->maxLevels,
							'newChildUrl' => 'entries/'.$section->handle.'/new',
						));
						$actions[] = $newChildAction;
					}
				}

				// Delete?
				if (
					$userSessionService->checkPermission('deleteEntries:'.$section->id) &&
					$userSessionService->checkPermission('deletePeerEntries:'.$section->id)
				)
				{
					$deleteAction = craft()->elements->getAction('Delete');
					$deleteAction->setParams(array(
						'confirmationMessage' => Craft::t('Are you sure you want to delete the selected entries?'),
						'successMessage'      => Craft::t('Entries deleted.'),
					));
					$actions[] = $deleteAction;
				}
			}
		}

		// Allow plugins to add additional actions
		$allPluginActions = craft()->plugins->call('addEntryActions', array($source), true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc IElementType::defineSortableAttributes()
	 *
	 * @return array
	 */
	public function defineSortableAttributes()
	{
		$attributes = array(
			'title'       => Craft::t('Title'),
			'uri'         => Craft::t('URI'),
			'postDate'    => Craft::t('Post Date'),
			'expiryDate'  => Craft::t('Expiry Date'),
			'dateCreated' => Craft::t('Date Created'),
			'dateUpdated' => Craft::t('Date Updated'),
		);

		// Allow plugins to modify the attributes
		craft()->plugins->call('modifyEntrySortableAttributes', array(&$attributes));

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::defineAvailableTableAttributes()
	 *
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		$attributes = array(
			'title'       => array('label' => Craft::t('Title')),
			'section'     => array('label' => Craft::t('Section')),
			'type'        => array('label' => Craft::t('Entry Type')),
			'author'      => array('label' => Craft::t('Author')),
			'slug'        => array('label' => Craft::t('Slug')),
			'uri'         => array('label' => Craft::t('URI')),
			'postDate'    => array('label' => Craft::t('Post Date')),
			'expiryDate'  => array('label' => Craft::t('Expiry Date')),
			'link'        => array('label' => Craft::t('Link'), 'icon' => 'world'),
			'id'          => array('label' => Craft::t('ID')),
			'dateCreated' => array('label' => Craft::t('Date Created')),
			'dateUpdated' => array('label' => Craft::t('Date Updated')),
		);

		// Hide Author from Craft Personal/Client
		if (craft()->getEdition() != Craft::Pro)
		{
			unset($attributes['author']);
		}

		// Allow plugins to modify the attributes
		$pluginAttributes = craft()->plugins->call('defineAdditionalEntryTableAttributes', array(), true);

		foreach ($pluginAttributes as $thisPluginAttributes)
		{
			$attributes = array_merge($attributes, $thisPluginAttributes);
		}

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::getDefaultTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public function getDefaultTableAttributes($source = null)
	{
		$attributes = array();

		if ($source == '*')
		{
			$attributes[] = 'section';
		}

		if ($source != 'singles')
		{
			$attributes[] = 'postDate';
			$attributes[] = 'expiryDate';
		}

		$attributes[] = 'author';
		$attributes[] = 'link';

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
		// First give plugins a chance to set this
		$pluginAttributeHtml = craft()->plugins->callFirst('getEntryTableAttributeHtml', array($element, $attribute), true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'author':
			{
				$author = $element->getAuthor();

				if ($author)
				{
					return craft()->templates->render('_elements/element', array(
						'element' => $author
					));
				}
				else
				{
					return '';
				}
			}

			case 'section':
			{
				$section = $element->getSection();

				return ($section ? Craft::t($section->name) : '');
			}

			case 'type':
			{
				$entryType = $element->getType();

				return ($entryType ? Craft::t($entryType->name) : '');
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
			'expiryDate'      => AttributeType::Mixed,
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

		if ($criteria->expiryDate)
		{
			$query->andWhere(DbHelper::parseDateParam('entries.expiryDate', $criteria->expiryDate, $query->params));
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
	 * @inheritDoc IElementType::getEagerLoadingMap()
	 *
	 * @param BaseElementModel[]  $sourceElements
	 * @param string $handle
	 *
	 * @return array|false
	 */
	public function getEagerLoadingMap($sourceElements, $handle)
	{
		if ($handle == 'author') {
			// Get the source element IDs
			$sourceElementIds = array();

			foreach ($sourceElements as $sourceElement) {
				$sourceElementIds[] = $sourceElement->id;
			}

			$map = craft()->db->createCommand()
				->select('id as source, authorId as target')
				->from('entries')
				->where(array('in', 'id', $sourceElementIds))
				->queryAll();

			return array(
				'elementType' => 'User',
				'map' => $map
			);
		}

		return parent::getEagerLoadingMap($sourceElements, $handle);
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
		$html = '';

		// Show the Entry Type field?
		if (!$element->id)
		{
			$entryTypes = $element->getSection()->getEntryTypes();

			if (count($entryTypes) > 1)
			{
				$entryTypeOptions = array();

				foreach ($entryTypes as $entryType)
				{
					$entryTypeOptions[] = array('label' => Craft::t($entryType->name), 'value' => $entryType->id);
				}

				$html .= craft()->templates->renderMacro('_includes/forms', 'selectField', array(
					array(
						'label' => Craft::t('Entry Type'),
						'id' => 'entryType',
						'value' => $element->typeId,
						'options' => $entryTypeOptions,
					)
				));

				$typeInputId = craft()->templates->namespaceInputId('entryType');
				$js = <<<EOD
$('#{$typeInputId}').on('change', function(ev) {
	var \$typeInput = $(this),
		editor = \$typeInput.closest('.hud').data('elementEditor');
	if (editor) {
		editor.setElementAttribute('typeId', \$typeInput.val());
		editor.loadHud();
	}
});
EOD;
				craft()->templates->includeJs($js);
			}
		}

		if ($element->getType()->hasTitleField)
		{
			$html .= craft()->templates->render('entries/_titlefield', array(
				'entry' => $element
			));
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
		// Make sure we have an author for this.
		if (!$element->authorId)
		{
			if (!empty($params['author']))
			{
				$element->authorId = $params['author'];
			}
			else
			{
				$element->authorId = craft()->userSession->getUser()->id;
			}
		}

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
			craft()->elements->updateElementSlugAndUri($element, true, true, true);
		}
	}

	// Protected methods
	// =========================================================================

	/**
	 * Preps the element criteria for a given table attribute
	 *
	 * @param ElementCriteriaModel $criteria
	 * @param string               $attribute
	 *
	 * @return void
	 */
	protected function prepElementCriteriaForTableAttribute(ElementCriteriaModel $criteria, $attribute)
	{
		if ($attribute == 'author')
		{
			$with = $criteria->with ?: array();
			$with[] = 'author';
			$criteria->with = $with;
		}
		else
		{
			parent::prepElementCriteriaForTableAttribute($criteria, $attribute);
		}
	}
}
