<?php

namespace Craft;

class HeaderHelper
{
	/**
	 * @param $extension
	 * @throws Exception
	 */
	public static function setContentTypeByExtension($extension)
	{
		$extension = strtolower($extension);
		$mimeTypes = require(Craft::getPathOfAlias('app.framework.utils.mimeTypes').'.php');

		if (!array_key_exists($extension, $mimeTypes))
		{
			Craft::log('Tried to set the header mime type for the extension '.$extension.', but could not find in the mimeTypes list.', LogLevel::Warning);
		}

		static::setHeader(array('Content-Type' => $mimeTypes[$extension].'; charset=utf-8'));
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
	 * Checks whether a header is currently set or not.
	 *
	 * @param $key
	 * @return bool
	 */
	public static function isHeaderSet($key)
	{
		// Grab existing headers.
		$currentHeaders = headers_list();
		$exists = false;

		foreach ($currentHeaders as $currentHeader)
		{
			// See if the existing header is in the "key: value" format.
			if (strpos($currentHeader, ':') !== false)
			{
				$currentParts = explode(':', $currentHeader);
				$currentKey = trim($currentParts[0]);
			}
			else
			{
				$currentKey = false;
			}

			if ($key == $currentKey)
			{
				$exists = true;
				break;
			}
		}

		return $exists;
	}

	/**
	 * Called to output a header.
	 *
	 * @param array $header Use key => value
	 * @return void
	 */
	public static function setHeader($header)
	{
		// Don't try to set headers when it's already too late
		if (true === headers_sent())
		{
			return false;
		}

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
	}
}
