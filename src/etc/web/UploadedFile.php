<?php
namespace Craft;

/**
 * Represents info for an uploaded file.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.web
 * @since     1.0
 */
class UploadedFile extends \CUploadedFile
{
	/**
	 * Returns an instance of the specified uploaded file.
	 *
	 * @param string $name
	 * @return \CUploadedFile|null
	 */
	public static function getInstanceByName($name)
	{
		$name = static::_normalizeName($name);
		return parent::getInstanceByName($name);
	}

	/**
	 * Returns an array of instances starting with specified array name.
	 *
	 * @param string $name
	 * @param bool $lookForSingleInstance
	 * @return array
	 */
	public static function getInstancesByName($name, $lookForSingleInstance = true)
	{
		$name = static::_normalizeName($name);
		$instances = parent::getInstancesByName($name);

		if (!$instances && $lookForSingleInstance)
		{
			$singleInstance = parent::getInstanceByName($name);

			if ($singleInstance)
			{
				$instances[] = $singleInstance;
			}
		}

		return $instances;
	}

	/**
	 * Swaps dot notation for the normal format.
	 *
	 * ex: fields.assetsField => fields[assetsField]
	 *
	 * @param string $name
	 * @return string
	 */
	private static function _normalizeName($name)
	{
		if (($pos = strpos($name, '.')) !== false)
		{
			// Convert dot notation to the normal format
			// ex: fields.assetsField => fields[assetsField]
			$name = substr($name, 0, $pos).'['.str_replace('.', '][', substr($name, $pos+1)).']';
		}

		return $name;
	}
}
