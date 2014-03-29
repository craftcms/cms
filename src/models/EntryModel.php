<?php
namespace Craft;

/**
 * Entry model class
 */
class EntryModel extends BaseElementModel
{
	protected $elementType = ElementType::Entry;

	const LIVE     = 'live';
	const PENDING  = 'pending';
	const EXPIRED  = 'expired';

	/**
	 * @access protected
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
			'parentId'   => AttributeType::Number,
		));
	}

	/**
	 * Returns the field layout used by this element.
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
	 * Returns the locale IDs this element is available in.
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
	 * Returns the URL format used to generate this element's URL.
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
	 * Returns the element's status.
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
	 * Returns whether the current user can edit the element.
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return craft()->userSession->checkPermission('publishEntries:'.$this->sectionId);
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		if ($this->getSection())
		{
			$url = UrlHelper::getCpUrl('entries/'.$this->getSection()->handle.'/'.$this->id);

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
	 * @return int|null
	 * @deprecated Deprecated in 2.0.
	 */
	public function depth()
	{
		craft()->deprecator->log('EntryModel::depth', 'Entries’ ‘depth’ property has been deprecated. Use ‘level’ instead.');
		return $this->level;
	}
}
