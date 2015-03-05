<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\db\Query;
use craft\app\elementactions\SetStatus;
use craft\app\enums\AttributeType;
use craft\app\enums\SectionType;
use craft\app\events\SetStatusEvent;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\DbHelper;
use craft\app\models\BaseElementModel;
use craft\app\base\Model;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\EntryType as EntryTypeModel;
use craft\app\models\Section as SectionModel;

/**
 * The Entry class is responsible for implementing and defining entries as a native element type in Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entry extends Element
{
	// Constants
	// =========================================================================

	const LIVE     = 'live';
	const PENDING  = 'pending';
	const EXPIRED  = 'expired';

	// Properties
	// =========================================================================

	/**
	 * @var integer Section ID
	 */
	public $sectionId;

	/**
	 * @var integer Type ID
	 */
	public $typeId;

	/**
	 * @var integer Author ID
	 */
	public $authorId;

	/**
	 * @var \DateTime Post date
	 */
	public $postDate;

	/**
	 * @var \DateTime Expiry date
	 */
	public $expiryDate;

	/**
	 * @var integer New parent ID
	 */
	public $newParentId;

	/**
	 * @var string Revision notes
	 */
	public $revisionNotes;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ElementInterface::hasContent()
	 *
	 * @return bool
	 */
	public static function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::hasTitles()
	 *
	 * @return bool
	 */
	public static function hasTitles()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::isLocalized()
	 *
	 * @return bool
	 */
	public static function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::hasStatuses()
	 *
	 * @return bool
	 */
	public static function hasStatuses()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementInterface::getStatuses()
	 *
	 * @return array|null
	 */
	public static function getStatuses()
	{
		return [
			Entry::LIVE => Craft::t('app', 'Live'),
			Entry::PENDING => Craft::t('app', 'Pending'),
			Entry::EXPIRED => Craft::t('app', 'Expired'),
			BaseElementModel::DISABLED => Craft::t('app', 'Disabled')
		];
	}

	/**
	 * @inheritDoc ElementInterface::getSources()
	 *
	 * @param null $context
	 *
	 * @return array|bool|false
	 */
	public static function getSources($context = null)
	{
		if ($context == 'index')
		{
			$sections = Craft::$app->sections->getEditableSections();
			$editable = true;
		}
		else
		{
			$sections = Craft::$app->sections->getAllSections();
			$editable = false;
		}

		$sectionIds       = [];
		$singleSectionIds = [];
		$sectionsByType   = [];

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

		$sources = [
			'*' => [
				'label'    => Craft::t('app', 'All entries'),
				'criteria' => ['sectionId' => $sectionIds, 'editable' => $editable]
			]
		];

		if ($singleSectionIds)
		{
			$sources['singles'] = [
				'label'    => Craft::t('app', 'Singles'),
				'criteria' => ['sectionId' => $singleSectionIds, 'editable' => $editable]
			];
		}

		$sectionTypes = [
			SectionType::Channel => Craft::t('app', 'Channels'),
			SectionType::Structure => Craft::t('app', 'Structures')
		];

		foreach ($sectionTypes as $type => $heading)
		{
			if (!empty($sectionsByType[$type]))
			{
				$sources[] = ['heading' => $heading];

				foreach ($sectionsByType[$type] as $section)
				{
					$key = 'section:'.$section->id;

					$sources[$key] = [
						'label'    => Craft::t('app', $section->name),
						'data'     => ['type' => $type, 'handle' => $section->handle],
						'criteria' => ['sectionId' => $section->id, 'editable' => $editable]
					];

					if ($type == SectionType::Structure)
					{
						$sources[$key]['structureId'] = $section->structureId;
						$sources[$key]['structureEditable'] = Craft::$app->getUser()->checkPermission('publishEntries:'.$section->id);
					}
				}
			}
		}

		return $sources;
	}

	/**
	 * @inheritDoc ElementInterface::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public static function getAvailableActions($source = null)
	{
		// Get the section(s) we need to check permissions on
		switch ($source)
		{
			case '*':
			{
				$sections = Craft::$app->sections->getEditableSections();
				break;
			}
			case 'singles':
			{
				$sections = Craft::$app->sections->getSectionsByType(SectionType::Single);
				break;
			}
			default:
			{
				if (preg_match('/^section:(\d+)$/', $source, $matches))
				{
					$section = Craft::$app->sections->getSectionById($matches[1]);
				}

				if (empty($section))
				{
					return;
				}

				$sections = [$section];
			}
		}

		// Now figure out what we can do with these
		$actions = [];
		$userSessionService = Craft::$app->getUser();
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
			$setStatusAction = Craft::$app->elements->getAction('SetStatus');
			$setStatusAction->on(SetStatus::EVENT_AFTER_SET_STATUS, function(SetStatusEvent $event)
			{
				if ($event->status == BaseElementModel::ENABLED)
				{
					// Set a Post Date as well
					Craft::$app->getDb()->createCommand()->update(
						'entries',
						['postDate' => DateTimeHelper::currentTimeForDb()],
						['and', ['in', 'id', $event->elementIds], 'postDate is null']
					)->execute();
				}
			});
			$actions[] = $setStatusAction;
		}

		// Edit
		if ($canEdit)
		{
			$editAction = Craft::$app->elements->getAction('Edit');
			$editAction->setParams([
				'label' => Craft::t('app', 'Edit entry'),
			]);
			$actions[] = $editAction;
		}

		if ($source == '*' || $source == 'singles' || $sections[0]->hasUrls)
		{
			// View
			$viewAction = Craft::$app->elements->getAction('View');
			$viewAction->setParams([
				'label' => Craft::t('app', 'View entry'),
			]);
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
				$structure = Craft::$app->structures->getStructureById($section->structureId);

				if ($structure)
				{
					$newChildAction = Craft::$app->elements->getAction('NewChild');
					$newChildAction->setParams([
						'label'       => Craft::t('app', 'Create a new child entry'),
						'maxLevels'   => $structure->maxLevels,
						'newChildUrl' => 'entries/'.$section->handle.'/new',
					]);
					$actions[] = $newChildAction;
				}
			}

			// Delete?
			if (
				$userSessionService->checkPermission('deleteEntries:'.$section->id) &&
				$userSessionService->checkPermission('deletePeerEntries:'.$section->id)
			)
			{
				$deleteAction = Craft::$app->elements->getAction('Delete');
				$deleteAction->setParams([
					'confirmationMessage' => Craft::t('app', 'Are you sure you want to delete the selected entries?'),
					'successMessage'      => Craft::t('app', 'Entries deleted.'),
				]);
				$actions[] = $deleteAction;
			}
		}

		// Allow plugins to add additional actions
		$allPluginActions = Craft::$app->plugins->call('addEntryActions', [$source], true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritDoc ElementInterface::defineSortableAttributes()
	 *
	 * @retrun array
	 */
	public static function defineSortableAttributes()
	{
		$attributes = [
			'title'      => Craft::t('app', 'Title'),
			'uri'        => Craft::t('app', 'URI'),
			'postDate'   => Craft::t('app', 'Post Date'),
			'expiryDate' => Craft::t('app', 'Expiry Date'),
		];

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyEntrySortableAttributes', [&$attributes]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementInterface::defineTableAttributes()
	 *
	 * @param string|null $source
	 *
	 * @return array
	 */
	public static function defineTableAttributes($source = null)
	{
		$attributes = [
			'title' => Craft::t('app', 'Title'),
			'uri'   => Craft::t('app', 'URI'),
		];

		if ($source == '*')
		{
			$attributes['section'] = Craft::t('app', 'Section');
		}

		if ($source != 'singles')
		{
			$attributes['postDate']   = Craft::t('app', 'Post Date');
			$attributes['expiryDate'] = Craft::t('app', 'Expiry Date');
		}

		// Allow plugins to modify the attributes
		Craft::$app->plugins->call('modifyEntryTableAttributes', [&$attributes, $source]);

		return $attributes;
	}

	/**
	 * @inheritDoc ElementInterface::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return mixed|null|string
	 */
	public static function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		// First give plugins a chance to set this
		$pluginAttributeHtml = Craft::$app->plugins->callFirst('getEntryTableAttributeHtml', [$element, $attribute], true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'section':
			{
				return Craft::t('app', $element->getSection()->name);
			}

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * @inheritDoc ElementInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public static function defineCriteriaAttributes()
	{
		return [
			'after'           => AttributeType::Mixed,
			'authorGroup'     => AttributeType::String,
			'authorGroupId'   => AttributeType::Number,
			'authorId'        => AttributeType::Number,
			'before'          => AttributeType::Mixed,
			'editable'        => AttributeType::Bool,
			'expiryDate'      => AttributeType::Mixed,
			'order'           => [AttributeType::String, 'default' => 'lft, postDate desc'],
			'postDate'        => AttributeType::Mixed,
			'section'         => AttributeType::Mixed,
			'sectionId'       => AttributeType::Number,
			'status'          => [AttributeType::String, 'default' => Entry::LIVE],
			'type'            => AttributeType::Mixed,
		];
	}

	/**
	 * @inheritDoc ElementInterface::getElementQueryStatusCondition()
	 *
	 * @param Query  $query
	 * @param string $status
	 *
	 * @return array|false|string|void
	 */
	public static function getElementQueryStatusCondition(Query $query, $status)
	{
		$currentTimeDb = DateTimeHelper::currentTimeForDb();

		switch ($status)
		{
			case Entry::LIVE:
			{
				return ['and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					"entries.postDate <= '{$currentTimeDb}'",
					['or', 'entries.expiryDate is null', "entries.expiryDate > '{$currentTimeDb}'"]
				];
			}

			case Entry::PENDING:
			{
				return ['and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					"entries.postDate > '{$currentTimeDb}'"
				];
			}

			case Entry::EXPIRED:
			{
				return ['and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					'entries.expiryDate is not null',
					"entries.expiryDate <= '{$currentTimeDb}'"
				];
			}
		}
	}

	/**
	 * @inheritDoc ElementInterface::modifyElementsQuery()
	 *
	 * @param Query                $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool|false|null|void
	 */
	public static function modifyElementsQuery(Query $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('entries.sectionId, entries.typeId, entries.authorId, entries.postDate, entries.expiryDate')
			->innerJoin('{{%entries}} entries', 'entries.id = elements.id')
			->innerJoin('{{%sections}} sections', 'sections.id = entries.sectionId')
			->leftJoin('{{%structures}} structures', 'structures.id = sections.structureId')
			->leftJoin('{{%structureelements}} structureelements', ['and', 'structureelements.structureId = structures.id', 'structureelements.elementId = entries.id']);

		if ($criteria->ref)
		{
			$refs = ArrayHelper::toArray($criteria->ref);
			$conditionals = [];

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
						$conditionals[] = ['and',
							DbHelper::parseParam('sections.handle', $parts[0], $query->params),
							DbHelper::parseParam('elements_i18n.slug', $parts[1], $query->params)
						];
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
			$typeIds = [];

			if (!is_array($criteria->type))
			{
				$criteria->type = [$criteria->type];
			}

			foreach ($criteria->type as $type)
			{
				if (is_numeric($type))
				{
					$typeIds[] = $type;
				}
				else if (is_string($type))
				{
					$types = Craft::$app->sections->getEntryTypesByHandle($type);

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
			$user = Craft::$app->getUser()->getIdentity();

			if (!$user)
			{
				return false;
			}

			// Limit the query to only the sections the user has permission to edit
			$editableSectionIds = Craft::$app->sections->getEditableSectionIds();
			$query->andWhere(['in', 'entries.sectionId', $editableSectionIds]);

			// Enforce the editPeerEntries permissions for non-Single sections
			$noPeerConditions = [];

			foreach (Craft::$app->sections->getEditableSections() as $section)
			{
				if (
					$section->type != SectionType::Single &&
					!$user->can('editPeerEntries:'.$section->id)
				)
				{
					$noPeerConditions[] = ['or', 'entries.sectionId != '.$section->id, 'entries.authorId = '.$user->id];
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

		if (Craft::$app->getEdition() >= Craft::Client)
		{
			if ($criteria->authorId)
			{
				$query->andWhere(DbHelper::parseParam('entries.authorId', $criteria->authorId, $query->params));
			}

			if ($criteria->authorGroupId || $criteria->authorGroup)
			{
				$query->innerJoin('{{%usergroups_users}} usergroups_users', 'usergroups_users.userId = entries.authorId');

				if ($criteria->authorGroupId)
				{
					$query->andWhere(DbHelper::parseParam('usergroups_users.groupId', $criteria->authorGroupId, $query->params));
				}

				if ($criteria->authorGroup)
				{
					$query->innerJoin('{{%usergroups}} usergroups', 'usergroups.id = usergroups_users.groupId');
					$query->andWhere(DbHelper::parseParam('usergroups.handle', $criteria->authorGroup, $query->params));
				}
			}
		}
	}

	/**
	 * @inheritDoc ElementInterface::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return BaseElementModel|Model|void
	 */
	public static function populateElementModel($row)
	{
		return Entry::populateModel($row);
	}

	/**
	 * @inheritDoc ElementInterface::getEditorHtml()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return string
	 */
	public static function getEditorHtml(BaseElementModel $element)
	{
		if ($element->getType()->hasTitleField)
		{
			$html = Craft::$app->templates->render('entries/_titlefield', [
				'entry' => $element
			]);
		}
		else
		{
			$html = '';
		}

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * @inheritdoc Element::saveElement()
	 *
	 * @return bool
	 */
	public static function saveElement(BaseElementModel $element, $params)
	{
		// Route this through \craft\app\services\Entries::saveEntry() so the proper entry events get fired.
		return Craft::$app->entries->saveEntry($element);
	}

	/**
	 * Routes the request when the URI matches an element.
	 *
	 * @param BaseElementModel $element
	 *
	 * @return array|bool|mixed
	 */
	public static function getElementRoute(BaseElementModel $element)
	{
		// Make sure that the entry is actually live
		if ($element->getStatus() == Entry::LIVE)
		{
			$section = $element->getSection();

			// Make sure the section is set to have URLs and is enabled for this locale
			if ($section->hasUrls && array_key_exists(Craft::$app->language, $section->getLocales()))
			{
				return ['templates/render', [
					'template' => $section->template,
					'variables' => [
						'entry' => $element
					]
				]];
			}
		}

		return false;
	}

	/**
	 * @inheritDoc ElementInterface::onAfterMoveElementInStructure()
	 *
	 * @param BaseElementModel $element
	 * @param int              $structureId
	 *
	 * @return null|void
	 */
	public static function onAfterMoveElementInStructure(BaseElementModel $element, $structureId)
	{
		// Was the entry moved within its section's structure?
		$section = $element->getSection();

		if ($section->type == SectionType::Structure && $section->structureId == $structureId)
		{
			Craft::$app->elements->updateElementSlugAndUri($element);
		}
	}

	// Instance Methods
	// -------------------------------------------------------------------------

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = [['sectionId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['typeId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['authorId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['postDate'], 'craft\\app\\validators\\DateTime'];
		$rules[] = [['expiryDate'], 'craft\\app\\validators\\DateTime'];
		$rules[] = [['newParentId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];

		return $rules;
	}

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$entryType = $this->getType();

		if ($entryType)
		{
			return $entryType->getFieldLayout();
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getLocales()
	 *
	 * @return array
	 */
	public function getLocales()
	{
		$locales = [];

		foreach ($this->getSection()->getLocales() as $locale)
		{
			$locales[$locale->locale] = ['enabledByDefault' => $locale->enabledByDefault];
		}

		return $locales;
	}

	/**
	 * @inheritDoc BaseElementModel::getUrlFormat()
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
		$section = $this->getSection();

		if ($section && $section->hasUrls)
		{
			$sectionLocales = $section->getLocales();

			if (isset($sectionLocales[$this->locale]))
			{
				if ($this->level > 1)
				{
					return $sectionLocales[$this->locale]->nestedUrlFormat;
				}
				else
				{
					return $sectionLocales[$this->locale]->urlFormat;
				}
			}
		}
	}

	/**
	 * Returns the reference string to this element.
	 *
	 * @return string|null
	 */
	public function getRef()
	{
		return $this->getSection()->handle.'/'.$this->slug;
	}

	/**
	 * Returns the entry's section.
	 *
	 * @return SectionModel|null
	 */
	public function getSection()
	{
		if ($this->sectionId)
		{
			return Craft::$app->sections->getSectionById($this->sectionId);
		}
	}

	/**
	 * Returns the type of entry.
	 *
	 * @return EntryTypeModel|null
	 */
	public function getType()
	{
		$section = $this->getSection();

		if ($section)
		{
			$sectionEntryTypes = $section->getEntryTypes('id');

			if ($sectionEntryTypes)
			{
				if ($this->typeId && isset($sectionEntryTypes[$this->typeId]))
				{
					return $sectionEntryTypes[$this->typeId];
				}
				else
				{
					// Just return the first one
					return ArrayHelper::getFirstValue($sectionEntryTypes);
				}
			}
		}
	}

	/**
	 * Returns the entry's author.
	 *
	 * @return UserModel|null
	 */
	public function getAuthor()
	{
		if ($this->authorId)
		{
			return Craft::$app->users->getUserById($this->authorId);
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getStatus()
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		if ($status == static::ENABLED && $this->postDate)
		{
			$currentTime = DateTimeHelper::currentTimeStamp();
			$postDate    = $this->postDate->getTimestamp();
			$expiryDate  = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

			if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime))
			{
				return static::LIVE;
			}
			else if ($postDate > $currentTime)
			{
				return static::PENDING;
			}
			else
			{
				return static::EXPIRED;
			}
		}

		return $status;
	}

	/**
	 * @inheritDoc BaseElementModel::isEditable()
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return (
			Craft::$app->getUser()->checkPermission('publishEntries:'.$this->sectionId) && (
				$this->authorId == Craft::$app->getUser()->getIdentity()->id ||
				Craft::$app->getUser()->checkPermission('publishPeerEntries:'.$this->sectionId) ||
				$this->getSection()->type == SectionType::Single
			)
		);
	}

	/**
	 * @inheritDoc BaseElementModel::getCpEditUrl()
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$section = $this->getSection();

		if ($section)
		{
			// The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
			$url = UrlHelper::getCpUrl('entries/'.$section->handle.'/'.$this->id.($this->slug ? '-'.$this->slug : ''));

			if (Craft::$app->isLocalized() && $this->locale != Craft::$app->language)
			{
				$url .= '/'.$this->locale;
			}

			return $url;
		}
	}
}
