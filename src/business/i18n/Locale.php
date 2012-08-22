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
		$id = static::getCanonicalID($id);
		$dataPath = static::$dataPath === null ? blx()->path->getFrameworkPath().'i18n/data' : static::$dataPath;
		$dataFile = $dataPath.'/'.$id.'.php';

		return is_file($dataFile);
	}
}
