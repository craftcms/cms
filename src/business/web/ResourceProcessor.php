<?php
namespace Blocks;

/**
 *
 */
class ResourceProcessor
{
	private $_rootFolderPath;
	private $_rootFolderUrl;
	private $_relResourcePath;
	private $_relResourceFolderName;
	private $_relResourceFileName;
	private $_resourceFullPath;
	private $_content;

	/**
	 * ResourceProcessor Constructor
	 *
	 * @param string $rootFolderPath The path to the root folder containing the hidden files
	 * @param string $rootFolderUrl The URL to the root folder containing the hidden files
	 * @param        $relResourcePath
	 */
	function __construct($rootFolderPath, $rootFolderUrl, $relResourcePath)
	{
		$this->_rootFolderPath = IOHelper::normalizePathSeparators($rootFolderPath);
		$this->_rootFolderUrl = IOHelper::normalizePathSeparators($rootFolderUrl);
		$this->_relResourcePath = IOHelper::normalizePathSeparators($relResourcePath);

		// Parse the relative resource path, separating the folder path from the filename
		$folderName = IOHelper::getFolderName($this->_relResourcePath);
		$this->_relResourceFolderName = $folderName !== '.' ? $folderName : '';
		$this->_relResourceFileName = IOHelper::getFileName($this->_relResourcePath);

		// Save the full server path
		$this->_resourceFullPath = $this->_rootFolderPath.$this->_relResourcePath;
	}

	/**
	 * Process the request
	 *
	 * @throws HttpException
	 */
	public function processResourceRequest()
	{
		if (IOHelper::fileExists($this->_resourceFullPath))
			$this->sendResource();
		else
			throw new HttpException(404);
	}

	/**
	 * Send the file back to the browser.
	 *
	 * @throws HttpException
	 */
	public function sendResource()
	{
		$this->_content = IOHelper::getFileContents($this->_resourceFullPath);

		if (!$this->_content)
			throw new HttpException(404);

		$mimeType = IOHelper::getMimeTypeByExtension($this->_resourceFullPath);

		if (strpos($mimeType, 'css') > 0)
			$this->_convertRelativeUrls();

		if (!blx()->config->useXSendFile)
			blx()->request->sendFile($this->_relResourceFileName, $this->_content);
		else
			blx()->request->xSendFile($this->_resourceFullPath);
	}

	/**
	 * Convert relative URLs in CSS files to absolute paths based on the root folder URL
	 *
	 * @access private
	 */
	private function _convertRelativeUrls()
	{
		$this->_content = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/', array(&$this, '_convertRelativeUrlMatch'), $this->_content);
	}

	/**
	 * @access private
	 * @param $match
	 * @return string
	 */
	private function _convertRelativeUrlMatch($match)
	{
		// ignore root-relative and absolute URLs
		if (preg_match('/^(\/|https?:\/\/)/', $match[3]))
		{
			return $match[0];
		}

		return $match[1].$this->_rootFolderUrl.$this->_relResourceFolderName.$match[3].$match[4];
	}
}
