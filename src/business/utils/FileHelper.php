<?php
namespace Blocks;

/**
 *
 */
class FileHelper extends \CFileHelper
{
	/**
	 * Returns a path's extension.
	 *
	 * @param string $path
	 * @param string $default
	 * @return null|string
	 */
	public static function getExtension($path, $default = null)
	{
		$extension = parent::getExtension($path);
		if ($extension)
			return $extension;
		else
			return $default;
	}
}
