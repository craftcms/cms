<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use yii\helpers\FileHelper;

/**
 * Class HeaderHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class HeaderHelper
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private static $_mimeType;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the MIME type that is going to be included in the response via
	 * the Content-Type header, whether that has been set explicitly in the PHP
	 * code or if it's going to be based on the default_mimetype setting in php.ini.
	 *
	 * @return string
	 */
	public static function getMimeType()
	{
		if (!isset(static::$_mimeType))
		{
			// Has it been explicitly set?
			static::$_mimeType = static::getHeader('Content-Type');

			if (static::$_mimeType !== null)
			{
				// Drop the charset, if it's there
				if (($pos = strpos(static::$_mimeType, ';')) !== false)
				{
					static::$_mimeType = rtrim(substr(static::$_mimeType, 0, $pos));
				}
			}
			else
			{
				// Then it's whatever's in php.ini
				static::$_mimeType = ini_get('default_mimetype');
			}
		}

		return static::$_mimeType;
	}

	/**
	 * Sets the Content-Type header based on a file extension.
	 *
	 * @param string $extension
	 *
	 * @return bool Whether setting the header was successful.
	 */
	public static function setContentTypeByExtension($extension)
	{
		$mimeType = FileHelper::getMimeTypeByExtension('.'.$extension);

		if (!$mimeType)
		{
			Craft::warning('Tried to set the header mime type for the extension '.$extension.', but could not find in the mimeTypes list.', __METHOD__);
			return false;
		}

		if (static::setHeader(['Content-Type' => $mimeType.'; charset=utf-8']))
		{
			// Save the MIME type for getMimeType()
			static::$_mimeType = $mimeType;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Tells the browser not to cache the following content
	 *
	 * @return null
	 */
	public static function setNoCache()
	{
		static::setExpires(-604800);
		static::setHeader(
			[
				'Pragma' => 'no-cache',
				'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
			]
		);
	}

	/**
	 * Tells the browser not to request this content again the next $sec seconds but use the browser cached content.
	 *
	 * @param int $seconds Time in seconds to hold in browser cache
	 *
	 * @return null
	 */
	public static function setExpires($seconds = 300)
	{
		static::setHeader(
			[
				'Expires' => gmdate('D, d M Y H:i:s', time() + $seconds).' GMT',
				'Cache-Control' => "max-age={$seconds}, public, s-maxage={$seconds}",
			]
		);
	}


	/**
	 * Tells the browser that the following content is private
	 *
	 * @return null
	 */
	public static function setPrivate()
	{
		static::setHeader(
			[
				'Pragma' => 'private',
				'Cache-control' => 'private, must-revalidate',
			]
		);
	}


	/**
	 * Tells the browser that the following content is public
	 *
	 * @return null
	 */
	public static function setPublic()
	{
		static::setHeader(
			[
				'Pragma' => 'public',
			]
		);
	}


	/**
	 * Forces a file download. Be sure to give the right extension.
	 *
	 * @param string  $filename The name of the file when it's downloaded
	 * @param int     $fileSize The size in bytes.
	 *
	 * @return null
	 */
	public static function setDownload($filename, $fileSize = null)
	{
		static::setHeader(
			[
				'Content-Description' => 'File Transfer',
				'Content-disposition' => 'attachment; filename="'.addslashes($filename).'"',
			]
		);

		// Add file size if provided
		if ((int) $fileSize > 0)
		{
			static::setLength($fileSize);
		}

		// For IE7
		static::setPrivate();
	}


	/**
	 * Tells the browser the length of the following content. This mostly makes sense when using the download function
	 * so the browser can calculate how many bytes are left during the process.
	 *
	 * @param int $sizeInBytes The content size in bytes
	 *
	 * @return null
	 */
	public static function setLength($sizeInBytes)
	{
		static::setHeader(['Content-Length' => (int)$sizeInBytes]);
	}

	/**
	 * Removes a header by key.
	 *
	 * @param $key
	 *
	 * @return null
	 */
	public static function removeHeader($key)
	{
		header_remove($key);
	}

	/**
	 * Checks whether a header is currently set or not.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function isHeaderSet($name)
	{
		return (static::getHeader($name) !== null);
	}

	/**
	 * Returns the value of a given header, if it has been set.
	 *
	 * @param string $name The name of the header.
	 *
	 * @return string|null The value of the header, or `null` if it hasn’t been set.
	 */
	public static function getHeader($name)
	{
		// Normalize to lowercase
		$name = strtolower($name);

		// Loop through each of the headers
		foreach (headers_list() as $header)
		{
			// Split it into its trimmed key/value
			$parts = array_map('trim', explode(':', $header, 2));

			// Is this the header we're looking for?
			if (isset($parts[1]) && $name == strtolower($parts[0]))
			{
				return $parts[1];
			}
		}
	}

	/**
	 * Called to output a header.
	 *
	 * @param array $header Use key => value
	 *
	 * @return null
	 */
	public static function setHeader($header)
	{
		// Don't try to set headers when it's already too late
		if (headers_sent())
		{
			return false;
		}

		// Clear out our stored MIME type in case its about to be overridden
		static::$_mimeType = null;

		if (is_string($header))
		{
			$header = [$header];
		}

		foreach ($header as $key => $value)
		{
			if (is_numeric($key))
			{
				header($value);
			}
			else
			{
				header("$key: $value");
			}
		}

		return true;
	}
}
