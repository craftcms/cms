<?php

class ResourceProcessor
{
	private $_rootFolderPath;
	private $_rootFolderUrl;
	private $_relResourcePath;
	private $_relResourceDirname;
	private $_relResourceFilename;
	private $_resourceFullPath;

	/**
	 * ResourceProcessor Constructor
	 * @param string $rootFolderPath The path to the root folder containing the hidden files
	 * @param string $rootFolderUrl The URL to the root folder containing the hidden files
	 * @param string $relativeResourcePath The path to the resource, relative from the root folder
	 */
	public function __construct($rootFolderPath, $rootFolderUrl, $relResourcePath)
	{
		$this->_rootFolderPath = rtrim($rootFolderPath, '/').'/';
		$this->_rootFolderUrl = rtrim($rootFolderUrl, '/').'/';
		$this->_relResourcePath = trim($relResourcePath, '/');

		// Parse the relative resource path, separating the directory path from the filename
		$pathinfo = pathinfo($this->_relResourcePath);
		$this->_relResourceDirname = isset($pathinfo['dirname']) && $pathinfo['dirname'] != '.' ? $pathinfo['dirname'].'/' : '';
		$this->_relResourceFilename = $pathinfo['filename'];

		// Save the full server path
		$this->_resourceFullPath = $this->_rootFolderPath . $this->_relResourcePath;
	}

	/**
	 * Process the request
	 */
	public function processResourceRequest()
	{
		if (file_exists($this->_resourceFullPath) && is_file($this->_resourceFullPath))
		{
			$this->sendResource();
		}
		else
		{
			$this->send404();
		}
	}

	/**
	 * Send the file back to the browser
	 */
	public function sendResource()
	{
		$this->_content = file_get_contents($this->_resourceFullPath);

		if (! $this->_content)
			$this->send404();

		$file = Blocks::app()->file->set($this->_resourceFullPath);
		$mimeType = $file->getMimeType();

		if (strpos($mimeType, 'css') > 0)
			$this->convertRelativeUrls();

		$file->send(false, false, $this->_content);
	}

	/**
	 * Convert relative URLs in CSS files to absolute paths based on the root folder URL
	 */
	private function convertRelativeUrls()
	{
		$this->_content = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/', array(&$this, 'convertRelativeUrlMatch'), $this->_content);
	}

	private function convertRelativeUrlMatch($match)
	{
		// ignore root-relative and absolute URLs
		if (preg_match('/^(\/|https?:\/\/)/', $match[3]))
		{
			return $match[0];
		}

		return $match[1].$this->_rootFolderUrl.$this->_relResourceDirname.$match[3].$match[4];
	}

	/**
	 * Sends a 404 error back to the client
	 */
	private function send404()
	{
		throw new BlocksHttpException(404, 'Page not found.');
	}
}
