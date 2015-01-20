<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\SectionType;

/**
 * Section model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Section extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_locales;

	/**
	 * @var
	 */
	private $_entryTypes;

	// Public Methods
	// =========================================================================

	/**
	 * Use the translated section name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t('app', $this->name);
	}

	/**
	 * Returns whether this is the homepage section.
	 *
	 * @return bool
	 */
	public function isHomepage()
	{
		return ($this->type == SectionType::Single && $this->urlFormat == '__home__');
	}

	/**
	 * Returns the section's locale models
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = Craft::$app->sections->getSectionLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = [];
			}
		}

		return $this->_locales;
	}

	/**
	 * Sets the section's locale models.
	 *
	 * @param array $locales
	 *
	 * @return null
	 */
	public function setLocales($locales)
	{
		$this->_locales = $locales;
	}

	/**
	 * Adds locale-specific errors to the model.
	 *
	 * @param array  $errors
	 * @param string $localeId
	 *
	 * @return null
	 */
	public function addLocaleErrors($errors, $localeId)
	{
		foreach ($errors as $attribute => $localeErrors)
		{
			$key = $attribute.'-'.$localeId;
			foreach ($localeErrors as $error)
			{
				$this->addError($key, $error);
			}
		}
	}

	/**
	 * Returns the section's entry types.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getEntryTypes($indexBy = null)
	{
		if (!isset($this->_entryTypes))
		{
			if ($this->id)
			{
				$this->_entryTypes = Craft::$app->sections->getEntryTypesBySectionId($this->id);
			}
			else
			{
				$this->_entryTypes = [];
			}
		}

		if (!$indexBy)
		{
			return $this->_entryTypes;
		}
		else
		{
			$entryTypes = [];

			foreach ($this->_entryTypes as $entryType)
			{
				$entryTypes[$entryType->$indexBy] = $entryType;
			}

			return $entryTypes;
		}
	}

	/**
	 * Returns the section's URL format (or URL) for the current locale.
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
		$locales = $this->getLocales();

		if ($locales)
		{
			$localeIds = array_keys($locales);

			// Does this section target the current locale?
			if (in_array(Craft::$app->language, $localeIds))
			{
				$localeId = Craft::$app->language;
			}
			else
			{
				$localeId = $localeIds[0];
			}

			return $locales[$localeId]->urlFormat;
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
		return [
			'id'                        => AttributeType::Number,
			'structureId'               => AttributeType::Number,
			'name'                      => AttributeType::String,
			'handle'                    => AttributeType::String,
			'type'                      => [AttributeType::Enum, 'values' => [SectionType::Single, SectionType::Channel, SectionType::Structure]],
			'hasUrls'                   => [AttributeType::Bool, 'default' => true],
			'template'                  => AttributeType::String,
			'maxLevels'                 => AttributeType::Number,
			'enableVersioning'          => [AttributeType::Bool, 'default' => true],
		];
	}
}
