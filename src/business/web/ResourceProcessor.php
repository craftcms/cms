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
	private $_relResourceDirName;
	private $_relResourceFileName;
	private $_resourceFullPath;
	private $_content;

	/**
	 * ResourceProcessor Constructor
	 * @param string $rootFolderPath The path to the root folder containing the hidden files
	 * @param string $rootFolderUrl The URL to the root folder containing the hidden files
	 * @param        $relResourcePath
	 * @internal param string $relativeResourcePath The path to the resource, relative from the root folder
	 */
	function __construct($rootFolderPath, $rootFolderUrl, $relResourcePath)
	{
		$this->_rootFolderPath = rtrim($rootFolderPath, '/').'/';
		$this->_rootFolderUrl = rtrim($rootFolderUrl, '/').'/';
		$this->_relResourcePath = trim($relResourcePath, '/');

		// Parse the relative resource path, separating the directory path from the filename
		$pathInfo = pathinfo($this->_relResourcePath);
		$this->_relResourceDirName = isset($pathInfo['dirname']) && $pathInfo['dirname'] != '.' ? $pathInfo['dirname'].'/' : '';
		$this->_relResourceFileName = basename($this->_relResourcePath);

		// Save the full server path
		$this->_resourceFullPath = $this->_rootFolderPath . $this->_relResourcePath;
	}

	/**
	 * Process the request
	 * @throws HttpException
	 */
	public function processResourceRequest()
	{
		if (file_exists($this->_resourceFullPath) && is_file($this->_resourceFullPath))
		{
			$this->sendResource();
		}
		else
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Send the file back to the browser.
	 * @throws HttpException
	 */
	public function sendResource()
	{
		$this->_content = file_get_contents($this->_resourceFullPath);

		if (! $this->_content)
			throw new HttpException(404);

		$file = blx()->file->set($this->_resourceFullPath);
		$mimeType = $file->getMimeType();

		if (strpos($mimeType, 'css') > 0)
			$this->_convertRelativeUrls();

		$file->send(false, false, $this->_content);
	}

	/**
	 * Convert relative URLs in CSS files to absolute paths based on the root folder URL
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

		return $match[1].$this->_rootFolderUrl.$this->_relResourceDirName.$match[3].$match[4];
	}
}
