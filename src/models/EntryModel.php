<?php
namespace Craft;

/**
 * Entry model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class EntryModel extends BaseElementModel
{
	// Constants
	// =========================================================================

	const LIVE     = 'live';
	const PENDING  = 'pending';
	const EXPIRED  = 'expired';

	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $elementType = ElementType::Entry;

	// Public Methods
	// =========================================================================

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
		$locales = array();

		foreach ($this->getSection()->getLocales() as $locale)
		{
			$locales[$locale->locale] = array('enabledByDefault' => $locale->enabledByDefault);
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
			return craft()->sections->getSectionById($this->sectionId);
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
					return $sectionEntryTypes[array_shift(array_keys($sectionEntryTypes))];
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
			return craft()->users->getUserById($this->authorId);
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
			craft()->userSession->checkPermission('publishEntries:'.$this->sectionId) && (
				$this->authorId == craft()->userSession->getUser()->id ||
				craft()->userSession->checkPermission('publishPeerEntries:'.$this->sectionId) ||
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

			if (craft()->isLocalized() && $this->locale != craft()->language)
			{
				$url .= '/'.$this->locale;
			}

			return $url;
		}
	}

	/**
	 * Returns the entry's level (formerly "depth").
	 *
	 * @deprecated Deprecated in 2.0. Use 'level' instead.
	 * @return int|null
	 */
	public function depth()
	{
		craft()->deprecator->log('EntryModel::depth', 'Entries’ ‘depth’ property has been deprecated. Use ‘level’ instead.');
		return $this->level;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'sectionId'  => AttributeType::Number,
			'typeId'     => AttributeType::Number,
			'authorId'   => AttributeType::Number,
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,

			// Just used for saving entries
			'parentId'      => AttributeType::Number,
			'revisionNotes' => AttributeType::String,
		));
	}
}
