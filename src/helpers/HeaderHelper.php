<?php

namespace Craft;

/**
 * Class HeaderHelper
 *
 * @package craft.app.helpers
 */
class HeaderHelper
{
	private static $_mimeType;

	/**
	 * Returns the MIME type that is going to be included in the response via the Content-Type header,
	 * whether that has been set explicitely in the PHP code or if it's going to be based on the default_mimetype setting in php.ini.
	 *
	 * @static
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
	 * @return bool Whether setting the header was successful.
	 */
	public static function setContentTypeByExtension($extension)
	{
		$extension = strtolower($extension);
		$mimeTypes = require(Craft::getPathOfAlias('app.framework.utils.mimeTypes').'.php');

		if (!isset($mimeTypes[$extension]))
		{
			Craft::log('Tried to set the header mime type for the extension '.$extension.', but could not find in the mimeTypes list.', LogLevel::Warning);
			return false;
		}

		$mimeType = $mimeTypes[$extension];

		if (static::setHeader(array('Content-Type' => $mimeType.'; charset=utf-8')))
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
	 * @return void
	 */
	public static function setNoCache()
	{
		static::setExpires(-604800);
		static::setHeader(
			array(
				'Pragma' => 'no-cache',
				'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
			)
		);
	}

	/**
	 * Tells the browser not to request this content again the next $sec seconds but use the browser cached content
	 *
	 * @param integer $seconds Time in seconds to hold in browser cache
	 * @return void
	 */
	public static function setExpires($seconds = 300)
	{
		static::setHeader(
			array(
				'Expires' => gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT',
				'Cache-Control' => "max-age={$seconds}, public, s-maxage={$seconds}",
			)
		);
	}


	/**
	 * Tells the browser that the following content is private
	 *
	 * @return void
	 */
	public static function setPrivate()
	{
		static::setHeader(
			array(
				'Pragma' => 'private',
				'Cache-control' => 'private, must-revalidate',
			)
		);
	}


	/**
	 * Tells the browser that the following content is public
	 *
	 * @return void
	 */
	public static function setPublic()
	{
		static::setHeader(
			array(
				'Pragma' => 'public',
			)
		);
	}


	/**
	 * Forces a file download. Be sure to give the right extension.
	 *
	 * @param string  $fileName The name of the file when it's downloaded
	 * @param integer $fileSize The size in bytes.
	 *
	 * @return void
	 */
	public static function setDownload($fileName, $fileSize = null)
	{
		static::setHeader(
			array(
				'Content-Description' => 'File Transfer',
				'Content-disposition' => 'attachment; filename="'.addslashes($fileName).'"',
			)
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
	 * Tells the browser the length of the following content.  This mostly makes sense when using the download function
	 * so the browser can calculate how many bytes are left during the process
	 *
	 * @param integer $sizeInBytes The content size in bytes
	 * @return void
	 */
	public static function setLength($sizeInBytes)
	{
		static::setHeader(array('Content-Length' => (int)$sizeInBytes)
		);
	}

	/**
	 * Removes a header by key.
	 *
	 * @param $key
	 */
	public static function removeHeader($key)
	{
		header_remove($key);
	}

	/**
	 * Checks whether a header is currently set.
	 *
	 * @param $name The name of the header.
	 * @return bool Whether the header has been set
	 */
	public static function isHeaderSet($name)
	{
		return (static::getHeader($name) !== null);
	}

	/**
	 * Returns the value of a given header, if it has been set.
	 *
	 * @static
	 * @param string $name The name of the header.
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
	 * Sets one or more response headers
	 *
	 * @param string|array $header Either a string in the "name: value" format, or an array of key/value pairs.
	 * @return bool Whether setting the header(s) was successful.
	 */
	public static function setHeader($header)
	{
		// Don't try to set headers when it's already too late
		if (true === headers_sent())
		{
			return false;
		}

		// Clear out our stored MIME type in case its about to be overridden
		static::$_mimeType = null;

		if (is_string($header))
		{
			$header = array($header);
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
