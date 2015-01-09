<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ElementType;
use craft\app\enums\SectionType;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\EntryType as EntryTypeModel;
use craft\app\models\FieldLayout as FieldLayoutModel;
use craft\app\models\Section as SectionModel;
use craft\app\models\User as UserModel;

/**
 * Entry model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entry extends BaseElementModel
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), [
			'sectionId'  => AttributeType::Number,
			'typeId'     => AttributeType::Number,
			'authorId'   => AttributeType::Number,
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,

			// Just used for saving entries
			'newParentId'   => AttributeType::Number,
			'revisionNotes' => AttributeType::String,
		]);
	}
}
