<?php
namespace Craft;

/**
 * Class FileHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     2.5
 */
class FileHelper extends \CFileHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Determines the file extension name based on a given MIME type, or a file path.
	 *
	 * This method will use a local map between MIME type and extension name.
	 *
	 * @param string $file      The file name or mime type
	 * @param string $magicFile The path of the file that contains all available extension information.
	 *                          If this is not set, the default 'system.utils.fileExtensions' file will be used.
	 *
	 * @return string|null The extension name. Null is returned if the extension cannot be determined.
	 */
	public static function getExtensionByMimeType($file, $magicFile = null)
	{
		static $mimeTypes, $customMimeTypes = array();

		if ($magicFile === null && $mimeTypes === null)
		{
			$mimeTypes = require(\Yii::getPathOfAlias('system.utils.fileExtensions').'.php');
		}
		else if ($magicFile !== null && !isset($customMimeTypes[$magicFile]))
		{
			$customMimeTypes[$magicFile] = require($magicFile);
		}

		// See if $file is actually a MIME type
		$mime = strtolower($file);

		if ($magicFile === null && isset($mimeTypes[$mime]))
		{
			return $mimeTypes[$mime];
		}
		else if ($magicFile !== null && isset($customMimeTypes[$magicFile][$mime]))
		{
			return $customMimeTypes[$magicFile][$mime];
		}

		return parent::getExtensionByMimeType($file, $magicFile);
	}
}
