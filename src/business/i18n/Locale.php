<?php
namespace Blocks;

/**
 *
 */
class Locale extends \CLocale
{
	/**
	 * @param $id
	 * @return bool
	 */
	public static function exists($id)
	{
		$id = self::getCanonicalID($id);
		$dataPath = self::$dataPath === null ? blx()->path->getFrameworkPath().'i18n/data' : self::$dataPath;
		$dataFile = $dataPath.'/'.$id.'.php';

		return is_file($dataFile);
	}
}
