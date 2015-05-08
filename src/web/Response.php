<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\helpers\IOHelper;
use yii\web\HttpException;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Response extends \yii\web\Response
{
	// Public Methods
	// =========================================================================

	/**
	 * Sets headers that will instruct the client to cache this response.
	 *
	 * @return static The response object itself.
	 */
	public function setCacheHeaders()
	{
		$headers = $this->getHeaders();

		$cacheTime = 31536000; // 1 year
		$headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $cacheTime).' GMT')
			->set('Pragma', 'cache')
			->set('Cache-Control', 'max-age='.$cacheTime);

		return $this;
	}

	/**
	 * Sets a Last-Modified header based on a given file path.
	 *
	 * @param string $path The file to read the last modified date from.
	 * @return static The response object itself.
	 */
	public function setLastModifiedHeader($path)
	{
		$modifiedTime = IOHelper::getLastTimeModified($path);

		if ($modifiedTime)
		{
			$this->getHeaders()->set('Last-Modified', gmdate("D, d M Y H:i:s", $modifiedTime->getTimestamp()).' GMT');
		}

		return $this;
	}

	/**
	 * @inheritdoc \yii\web\Response::sendFile()
	 *
	 * @param string $filePath
	 * @param string $attachmentName
	 * @param array $options
	 * @return static
	 */
	public function sendFile($filePath, $attachmentName = null, $options = [])
	{
		$this->_clearOutputBuffer();
		return parent::sendFile($filePath, $attachmentName, $options);
	}

	/**
	 * @inheritdoc \yii\web\Response::sendContentAsFile()
	 *
	 * @param string $content
	 * @param string $attachmentName
	 * @param array $options
	 * @return static
	 * @throws HttpException
	 */
	public function sendContentAsFile($content, $attachmentName, $options = [])
	{
		$this->_clearOutputBuffer();
		return parent::sendContentAsFile($content, $attachmentName, $options);
	}

	/**
	 * Attempts to closes the connection with the HTTP client, without ending PHP script execution.
	 *
	 * This method relies on [flush()](http://php.net/manual/en/function.flush.php), which may not actually work if
	 * mod_deflate or mod_gzip is installed, or if this is a Win32 server.
	 *
	 * @see http://stackoverflow.com/a/141026
	 * @throws Exception An exception will be thrown if content has already been output.
	 * @return null
	 */
	public function sendAndClose()
	{
		// Make sure nothing has been output yet
		if (headers_sent())
		{
			return;
		}

		// Prevent the script from ending when the browser closes the connection
		ignore_user_abort(true);

		// Prepend any current OB content
		while (ob_get_length() !== false)
		{
			// If ob_start() didn't have the PHP_OUTPUT_HANDLER_CLEANABLE flag, ob_get_clean() will cause a PHP notice
			// and return false.
			$obContent = @ob_get_clean();

			if ($obContent !== false)
			{
				$this->content = $obContent . $this->content;
			}
			else
			{
				break;
			}
		}

		// Tell the browser to close the connection
		$length = $this->content !== null ? strlen($this->content) : 0;
		$this->getHeaders()
			->set('Connection', 'close')
			->set('Content-Length', $length);

		$this->send();

		// Close the session.
		Craft::$app->getSession()->close();
	}

	// Private Methods
	// =========================================================================

	/**
	 * Clear the output buffer to prevent corrupt downloads.
	 *
	 * Need to check the OB status first, or else some PHP versions will throw an E_NOTICE
	 * since we have a custom error handler (http://pear.php.net/bugs/bug.php?id=9670).
	 */
	private function _clearOutputBuffer()
	{
		if (ob_get_length() !== false)
		{
			// If zlib.output_compression is enabled, then ob_clean() will corrupt the results of output buffering.
			// ob_end_clean is what we want.
			ob_end_clean();
		}
	}
}
