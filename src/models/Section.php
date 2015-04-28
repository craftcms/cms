<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * Section model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Section extends Model
{
	// Constants
	// =========================================================================

	const TYPE_SINGLE    = 'single';
	const TYPE_CHANNEL   = 'channel';
	const TYPE_STRUCTURE = 'structure';

	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Structure ID
	 */
	public $structureId;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var string Type
	 */
	public $type;

	/**
	 * @var boolean Has URLs
	 */
	public $hasUrls = true;

	/**
	 * @var string Template
	 */
	public $template;

	/**
	 * @var integer Max levels
	 */
	public $maxLevels;

	/**
	 * @var boolean Enable versioning
	 */
	public $enableVersioning = true;


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
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['structureId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['type'], 'in', 'range' => ['single', 'channel', 'structure']],
			[['maxLevels'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'structureId', 'name', 'handle', 'type', 'hasUrls', 'template', 'maxLevels', 'enableVersioning'], 'safe', 'on' => 'search'],
		];
	}

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
		return ($this->type == self::TYPE_SINGLE && $this->urlFormat == '__home__');
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
				$this->_locales = Craft::$app->getSections()->getSectionLocales($this->id, 'locale');
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
				$this->_entryTypes = Craft::$app->getSections()->getEntryTypesBySectionId($this->id);
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
}
