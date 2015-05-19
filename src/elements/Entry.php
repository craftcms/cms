<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\elements\actions\Delete;
use craft\app\elements\actions\Edit;
use craft\app\elements\actions\NewChild;
use craft\app\elements\actions\SetStatus;
use craft\app\elements\actions\View;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\db\EntryQuery;
use craft\app\events\SetStatusEvent;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\EntryType;
use craft\app\models\Section;

/**
 * Entry represents an entry element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entry extends Element
{
	// Constants
	// =========================================================================

	const STATUS_LIVE     = 'live';
	const STATUS_PENDING  = 'pending';
	const STATUS_EXPIRED  = 'expired';

	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function displayName()
	{
		return Craft::t('app', 'Entry');
	}

	/**
	 * @inheritdoc
	 */
	public static function hasContent()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function hasTitles()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function hasStatuses()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function getStatuses()
	{
		return [
			static::STATUS_LIVE => Craft::t('app', 'Live'),
			static::STATUS_PENDING => Craft::t('app', 'Pending'),
			static::STATUS_EXPIRED => Craft::t('app', 'Expired'),
			static::STATUS_DISABLED => Craft::t('app', 'Disabled')
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return EntryQuery The newly created [[EntryQuery]] instance.
	 */
	public static function find()
	{
		return new EntryQuery(get_called_class());
	}

	/**
	 * @inheritdoc
	 */
	public static function getSources($context = null)
	{
		if ($context == 'index')
		{
			$sections = Craft::$app->getSections()->getEditableSections();
			$editable = true;
		}
		else
		{
			$sections = Craft::$app->getSections()->getAllSections();
			$editable = false;
		}

		$sectionIds       = [];
		$singleSectionIds = [];
		$sectionsByType   = [];

		foreach ($sections as $section)
		{
			$sectionIds[] = $section->id;

			if ($section->type == Section::TYPE_SINGLE)
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
			Section::TYPE_CHANNEL => Craft::t('app', 'Channels'),
			Section::TYPE_STRUCTURE => Craft::t('app', 'Structures')
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

					if ($type == Section::TYPE_STRUCTURE)
					{
						$sources[$key]['structureId'] = $section->structureId;
						$sources[$key]['structureEditable'] = Craft::$app->getUser()->checkPermission('publishEntries:'.$section->id);
					}
				}
			}
		}

		// Allow plugins to modify the sources
		Craft::$app->getPlugins()->call('modifyEntrySources', [&$sources, $context]);

		return $sources;
	}

	/**
	 * @inheritdoc
	 */
	public static function getAvailableActions($source = null)
	{
		// Get the section(s) we need to check permissions on
		switch ($source)
		{
			case '*':
			{
				$sections = Craft::$app->getSections()->getEditableSections();
				break;
			}
			case 'singles':
			{
				$sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
				break;
			}
			default:
			{
				if (preg_match('/^section:(\d+)$/', $source, $matches))
				{
					$section = Craft::$app->getSections()->getSectionById($matches[1]);
				}

				if ($section)
				{
					$sections = [$section];
				}
			}
		}

		// Now figure out what we can do with these
		$actions = [];

		if (!empty($sections))
		{
			$userSessionService = Craft::$app->getUser();
			$canSetStatus = true;
			$canEdit = false;

			foreach ($sections as $section)
			{
				$canPublishEntries = $userSessionService->checkPermission('publishEntries:'.$section->id);

				// Only show the Set Status action if we're sure they can make changes in all the sections
				if (!(
					$canPublishEntries &&
					($section->type == Section::TYPE_SINGLE || $userSessionService->checkPermission('publishPeerEntries:'.$section->id))
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
				/** @var SetStatus $setStatusAction */
				$setStatusAction = Craft::$app->getElements()->createAction(SetStatus::className());
				$setStatusAction->on(SetStatus::EVENT_AFTER_SET_STATUS, function (SetStatusEvent $event)
				{
					if ($event->status == static::STATUS_ENABLED)
					{
						// Set a Post Date as well
						Craft::$app->getDb()->createCommand()->update(
							'{{%entries}}',
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
				$actions[] = Craft::$app->getElements()->createAction([
					'type' => Edit::className(),
					'label' => Craft::t('app', 'Edit entry'),
				]);
			}

			if ($source == '*' || $source == 'singles' || $sections[0]->hasUrls)
			{
				// View
				$actions[] = Craft::$app->getElements()->createAction([
					'type' => View::className(),
					'label' => Craft::t('app', 'View entry'),
				]);
			}

			// Channel/Structure-only actions
			if ($source != '*' && $source != 'singles')
			{
				$section = $sections[0];

				// New child?
				if (
					$section->type == Section::TYPE_STRUCTURE &&
					$userSessionService->checkPermission('createEntries:'.$section->id)
				)
				{
					$structure = Craft::$app->getStructures()->getStructureById($section->structureId);

					if ($structure)
					{
						$actions[] = Craft::$app->getElements()->createAction([
							'type' => NewChild::className(),
							'label' => Craft::t('app', 'Create a new child entry'),
							'maxLevels' => $structure->maxLevels,
							'newChildUrl' => 'entries/'.$section->handle.'/new',
						]);
					}
				}

				// Delete?
				if (
					$userSessionService->checkPermission('deleteEntries:'.$section->id) &&
					$userSessionService->checkPermission('deletePeerEntries:'.$section->id)
				)
				{
					$actions[] = Craft::$app->getElements()->createAction([
						'type' => Delete::className(),
						'confirmationMessage' => Craft::t('app', 'Are you sure you want to delete the selected entries?'),
						'successMessage' => Craft::t('app', 'Entries deleted.'),
					]);
				}
			}
		}

		// Allow plugins to add additional actions
		$allPluginActions = Craft::$app->getPlugins()->call('addEntryActions', [$source], true);

		foreach ($allPluginActions as $pluginActions)
		{
			$actions = array_merge($actions, $pluginActions);
		}

		return $actions;
	}

	/**
	 * @inheritdoc
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
		Craft::$app->getPlugins()->call('modifyEntrySortableAttributes', [&$attributes]);

		return $attributes;
	}

	/**
	 * @inheritdoc
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
		Craft::$app->getPlugins()->call('modifyEntryTableAttributes', [&$attributes, $source]);

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public static function getTableAttributeHtml(ElementInterface $element, $attribute)
	{
		/** @var Entry $element */
		// First give plugins a chance to set this
		$pluginAttributeHtml = Craft::$app->getPlugins()->callFirst('getEntryTableAttributeHtml', [$element, $attribute], true);

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
	 * @inheritdoc
	 */
	public static function getElementQueryStatusCondition(ElementQueryInterface $query, $status)
	{
		$currentTimeDb = DateTimeHelper::currentTimeForDb();

		switch ($status)
		{
			case Entry::STATUS_LIVE:
			{
				return ['and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					"entries.postDate <= '{$currentTimeDb}'",
					['or', 'entries.expiryDate is null', "entries.expiryDate > '{$currentTimeDb}'"]
				];
			}

			case Entry::STATUS_PENDING:
			{
				return ['and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					"entries.postDate > '{$currentTimeDb}'"
				];
			}

			case Entry::STATUS_EXPIRED:
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
	 * @inheritdoc
	 */
	public static function getEditorHtml(ElementInterface $element)
	{
		/** @var Entry $element */
		if ($element->getType()->hasTitleField)
		{
			$html = Craft::$app->getView()->renderTemplate('entries/_titlefield', [
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
	public static function saveElement(ElementInterface $element, $params)
	{
		/** @var Entry $element */
		// Route this through \craft\app\services\Entries::saveEntry() so the proper entry events get fired.
		return Craft::$app->getEntries()->saveEntry($element);
	}

	/**
	 * Routes the request when the URI matches an element.
	 *
	 * @param ElementInterface $element
	 *
	 * @return array|bool|mixed
	 */
	public static function getElementRoute(ElementInterface $element)
	{
		/** @var Entry $element */
		// Make sure that the entry is actually live
		if ($element->getStatus() == Entry::STATUS_LIVE)
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
	 * @inheritdoc
	 */
	public static function onAfterMoveElementInStructure(ElementInterface $element, $structureId)
	{
		/** @var Entry $element */
		// Was the entry moved within its section's structure?
		$section = $element->getSection();

		if ($section->type == Section::TYPE_STRUCTURE && $section->structureId == $structureId)
		{
			Craft::$app->getElements()->updateElementSlugAndUri($element, true, true, true);
		}
	}

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
	 * @inheritdoc
	 */
	public function datetimeAttributes()
	{
		$names = parent::datetimeAttributes();
		$names[] = 'postDate';
		$names[] = 'expiryDate';
		return $names;
	}

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
	 * @inheritdoc
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
	 * @inheritdoc
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
	 * @inheritdoc
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
	 * @return Section|null
	 */
	public function getSection()
	{
		if ($this->sectionId)
		{
			return Craft::$app->getSections()->getSectionById($this->sectionId);
		}
	}

	/**
	 * Returns the type of entry.
	 *
	 * @return EntryType|null
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
	 * @return User|null
	 */
	public function getAuthor()
	{
		if ($this->authorId)
		{
			return Craft::$app->getUsers()->getUserById($this->authorId);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		if ($status == self::STATUS_ENABLED && $this->postDate)
		{
			$currentTime = DateTimeHelper::currentTimeStamp();
			$postDate    = $this->postDate->getTimestamp();
			$expiryDate  = ($this->expiryDate ? $this->expiryDate->getTimestamp() : null);

			if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime))
			{
				return self::STATUS_LIVE;
			}
			else if ($postDate > $currentTime)
			{
				return self::STATUS_PENDING;
			}
			else
			{
				return self::STATUS_EXPIRED;
			}
		}

		return $status;
	}

	/**
	 * @inheritdoc
	 */
	public function isEditable()
	{
		return (
			Craft::$app->getUser()->checkPermission('publishEntries:'.$this->sectionId) && (
				$this->authorId == Craft::$app->getUser()->getIdentity()->id ||
				Craft::$app->getUser()->checkPermission('publishPeerEntries:'.$this->sectionId) ||
				$this->getSection()->type == Section::TYPE_SINGLE
			)
		);
	}

	/**
	 * @inheritdoc
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function resolveStructureId()
	{
		return $this->getSection()->structureId;
	}
}
