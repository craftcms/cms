<?php
namespace Craft;

/**
 *
 */
class LocaleData extends \CLocale
{
	/**
	 * Overriding getLanguage() from \CLocale because this is where we do want
	 * to chop off the territory half of a locale ID.
	 */
	public function getLanguage($id)
	{
		$id = $this->getLanguageID($id);
		return $this->getLocaleDisplayName($id, 'languages');
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function exists($id)
	{
		$id = static::getCanonicalID($id);
		$dataPath = static::$dataPath === null ? craft()->path->getFrameworkPath().'i18n/data' : static::$dataPath;
		$dataFile = $dataPath.'/'.$id.'.php';

		return IOHelper::fileExists($dataFile);
	}
}
