<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use craft\app\assetsourcetypes\BaseAssetSourceType;
use craft\app\models\AssetFile as AssetFileModel;

/**
 * Class AssetsHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetsHelper
{
	// Constants
	// =========================================================================

	const INDEX_SKIP_ITEMS_PATTERN = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

	// Public Methods
	// =========================================================================

	/**
	 * Get a temporary file path.
	 *
	 * @param string $extension extension to use. "tmp" by default.
	 *
	 * @return mixed
	 */
	public static function getTempFilePath($extension = 'tmp')
	{
		$extension = strpos($extension, '.') !== false ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
		$fileName = uniqid('assets', true).'.'.$extension;

		return IOHelper::createFile(craft()->path->getTempPath().$fileName)->getRealPath();
	}

	/**
	 * Generate a URL for a given Assets file in a Source Type.
	 *
	 * @param BaseAssetSourceType $sourceType
	 * @param AssetFileModel      $file
	 *
	 * @return string
	 */
	public static function generateUrl(BaseAssetSourceType $sourceType, AssetFileModel $file)
	{
		$baseUrl = $sourceType->getBaseUrl();
		$folderPath = $file->getFolder()->path;
		$fileName = $file->filename;
		$appendix = static::getUrlAppendix($sourceType, $file);

		return $baseUrl.$folderPath.$fileName.$appendix;
	}

	/**
	 * Get appendix for an URL based on it's Source caching settings.
	 *
	 * @param BaseAssetSourceType $source
	 * @param AssetFileModel      $file
	 *
	 * @return string
	 */
	public static function getUrlAppendix(BaseAssetSourceType $source, AssetFileModel $file)
	{
		$appendix = '';

		if (!empty($source->getSettings()->expires) && DateTimeHelper::isValidIntervalString($source->getSettings()->expires))
		{
			$appendix = '?mtime='.$file->dateModified->format("YmdHis");
		}

		return $appendix;
	}

	/**
	 * Clean an Asset's filename.
	 *
	 * @param $name
	 * @param bool $isFilename if set to true (default), will separate extension
	 *                         and clean the filename separately.
	 *
	 * @return mixed
	 */
	public static function cleanAssetName($name, $isFilename = true)
	{
		if ($isFilename)
		{
			$baseName = IOHelper::getFileName($name, false);
			$extension = '.'.IOHelper::getExtension($name);
		}
		else
		{
			$baseName = $name;
			$extension =  '';
		}


		$separator = craft()->config->get('filenameWordSeparator');

		if (!is_string($separator))
		{
			$separator = null;
		}

		$baseName = IOHelper::cleanFilename($baseName, false, $separator);

		if ($isFilename && empty($baseName))
		{
			$baseName = '-';
		}

		return $baseName.$extension;
	}
}
