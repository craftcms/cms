<?php
namespace Craft;

/**
 * Class AssetsHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
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

		$baseName = IOHelper::cleanFilename($baseName, craft()->config->get('convertFilenamesToAscii'), $separator);

		if ($isFilename && empty($baseName))
		{
			$baseName = '-';
		}

		return $baseName.$extension;
	}

	/**
	 * Return a filename replacement for a filename in a list of files. The file
	 * list typically represents a folder's contents.
	 *
	 * @param array $fileList
	 * @param       $originalFilename
	 *
	 * @throws Exception When a replacement cannot be found.
	 * @return string $filename
	 */
	public static function getFilenameReplacement(array $fileList, $originalFilename)
	{
		foreach ($fileList as &$file)
		{
			$file = StringHelper::toLowerCase($file);
		}

		$fileList = array_flip($fileList);

		// Shorthand.
		$canUse = function ($filenameToTest) use ($fileList)
		{
			return !isset($fileList[StringHelper::toLowerCase($filenameToTest)]);
		};

		if ($canUse($originalFilename))
		{
			return $originalFilename;
		}

		$extension = IOHelper::getExtension($originalFilename);
		$filename = IOHelper::getFileName($originalFilename, false);

		// If the file already ends with something that looks like a timestamp, use that instead.
		if (preg_match('/.*_([0-9]{6}_[0-9]{6})$/', $filename, $matches))
		{
			$base = $filename;
		}
		else
		{
			$timestamp = DateTimeHelper::currentUTCDateTime()->format("ymd_His");
			$base = $filename.'_'.$timestamp;
		}

		$newFilename = $base.'.'.$extension;

		if ($canUse($newFilename))
		{
			return $newFilename;
		}

		$increment = 0;

		while (++$increment)
		{
			$newFilename = $base.'_'.$increment.'.'.$extension;

			if ($canUse($newFilename))
			{
				break;
			}

			if ($increment == 50)
			{
				throw new Exception(Craft::t("A suitable replacement name cannot be found for “{filename}”", array('filename' => $originalFilename)));
			}
		}

		return $newFilename;
	}
}
