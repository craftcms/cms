<?php
namespace Craft;

/**
 * Class LocaleData
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.i18n
 * @since     1.0
 */
class LocaleData extends \CLocale
{
	// Properties
	// =========================================================================

	private $_territories;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the instance of the specified locale. Since the constructor of CLocale is protected, you can only use
	 * this method to obtain an instance of the specified locale.
	 *
	 * @param  string $id The locale ID (e.g. en_US)
	 *
	 * @return LocaleData The locale instance
	 */
	public static function getInstance($id)
	{
		static $locales = array();

		if (isset($locales[$id]))
		{
			return $locales[$id];
		}
		else
		{
			return $locales[$id] = new LocaleData($id);
		}
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public static function exists($id)
	{
		$id = static::getCanonicalID($id);
		$dataPath = static::$dataPath === null ? craft()->path->getFrameworkPath().'i18n/data' : static::$dataPath;
		$dataFile = $dataPath.'/'.$id.'.php';

		return IOHelper::fileExists($dataFile);
	}

	/**
	 * Converts a locale ID to a language ID.  Language ID consists of only the first group of letters before an
	 * underscore or dash.
	 *
	 * Craft overrides the parent method from {@link \CLocale} because this is where we want to chop off the territory
	 * half of a locale ID.
	 *
	 * @param string $id The locale ID to be converted
	 *
	 * @return string The language ID
	 */
	public function getLanguage($id)
	{
		$id = $this->getLanguageID($id);
		return $this->getLocaleDisplayName($id, 'languages');
	}

	/**
	 * @return NumberFormatter
	 */
	public function getNumberFormatter()
	{
		if ($this->_numberFormatter === null)
		{
			$this->_numberFormatter = new NumberFormatter($this);
		}

		return $this->_numberFormatter;
	}

	/**
	 * @return DateFormatter
	 */
	public function getDateFormatter()
	{
		if ($this->_dateFormatter === null)
		{
			$this->_dateFormatter = new DateFormatter($this);
		}

		return $this->_dateFormatter;
	}

	/**
	 * Returns an array of territories for the locale instance or null, if none
	 * exist.
	 *
	 * @return string[]|null An array of all territories for the given locale,
	 *                       or null, if none exist.
	 */
	public function getAllTerritories()
	{
		if (!$this->_territories)
		{
			if (isset($this->_data['territories']))
			{
				$territories = $this->_data['territories'];

				foreach ($territories as $key => $territory)
				{
					$this->_territories[] = $this->getTerritory($key);
				}
			}
		}

		return $this->_territories;
	}

}
