<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\i18n;

use Craft;
use craft\app\helpers\IOHelper;

/**
 * Class LocaleData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		static $locales = [];

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
		$dataPath = static::$dataPath === null ? Craft::$app->path->getFrameworkPath().'i18n/data' : static::$dataPath;
		$dataFile = $dataPath.'/'.$id.'.php';

		return IOHelper::fileExists($dataFile);
	}

	/**
	 * Converts a locale ID to a language ID.  Language ID consists of only the first group of letters before an
	 * underscore or dash.
	 *
	 * Craft overrides the parent method from [[\CLocale]] because this is where we want to chop off the territory
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
