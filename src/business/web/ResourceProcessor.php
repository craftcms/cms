<?php

class ResourceProcessor
{
	private $_pluginHandle;
	private $_relativeResourcePath;
	private $_relativeResourceName;
	private $_relativeResourcePathAndName;

	function __construct()
	{
		$this->_pluginHandle = Blocks::app()->request->getQuery('pluginHandle', null);
		$this->_relativeResourcePathAndName = Blocks::app()->request->getQuery('resourcePath', null);
		$this->parseRelativeResourcePath($this->_relativeResourcePathAndName);
	}

	public function processResourceRequest()
	{
		$resourceFullPath = $this->translateResourcePaths($this->_relativeResourcePathAndName);

		if(file_exists($resourceFullPath))
		{
			$this->sendResource($resourceFullPath);
		}
		else
		{
			// error
		}
	}

	public function translateResourcePaths()
	{
		// plugin resource
		if($this->_pluginHandle !== null)
		{
			return Blocks::app()->configRepo->getBlocksPluginsPath().$this->_pluginHandle.DIRECTORY_SEPARATOR.$this->_relativeResourcePathAndName;
		}
		// blocks resource
		else
		{
			return Blocks::app()->configRepo->getBlocksResourcesPath().$this->_relativeResourcePathAndName;
		}
	}

	private function parseRelativeResourcePath()
	{
		// if the first char is a '/', then strip it.
		if(strpos($this->_relativeResourcePathAndName, '/') == 0)
		{
			$this->_relativeResourcePathAndName = ltrim($this->_relativeResourcePathAndName, '/');
		}

		$slashCount = substr_count($this->_relativeResourcePathAndName, '/');

		// bg.gif
		if ($slashCount == 0)
		{
			$this->_relativeResourcePath = null;
			$this->_relativeResourceName = $this->_relativeResourcePathAndName;
		}
		else
		{
			// dir1/bg.gif
			// dir1/dir2/bg.gif
			if ($slashCount > 0)
			{
				$lastSlashPos = strrpos($this->_relativeResourcePathAndName, '/');
				$this->_relativeResourceName = substr($this->_relativeResourcePathAndName, $lastSlashPos + 1);
				$this->_relativeResourcePath = substr($this->_relativeResourcePathAndName, 0, $lastSlashPos + 1);
			}
			else
			{
				// error, invalid path.
			}
		}
	}

	public function correctImagePaths($content)
	{
		return preg_replace('/url\((\')??((http(s)?\:\/\/)?.+)(\')?\)/U', 'url($5'.Blocks::app()->configRepo->getBlocksResourceProcessorUrl().'?resourcePath='.$this->_relativeResourcePath.'$2$5)', ''.$content.'');
	}

	public function getMimeTypeByExtension($file)
	{
		$extensions = require_once(Blocks::app()->configRepo->getBlocksFrameworkPath().'utils'.DIRECTORY_SEPARATOR.'mimeTypes.php');

		if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '')
		{
			$ext = strtolower($ext);

			if (isset($extensions[$ext]))
				return $extensions[$ext];
		}

		// return text by default
		return 'text/plain';
	}

	public function sendResource($resourceFullPath)
	{
		$content = file_get_contents($resourceFullPath);
		$mimeType = $this->getMimeTypeByExtension($resourceFullPath);

		if(strpos($mimeType, 'css') > 0)
			$content = $this->correctImagePaths($content);

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Content-type: $mimeType");

		if(ini_get("output_handler") == '')
			header('Content-Length: '.(function_exists('mb_strlen') ? mb_strlen($content, '8bit') : strlen($content)));

		header("Content-Disposition: attachment; filename=\"$resourceFullPath\"");
		header('Content-Transfer-Encoding: binary');

		echo $content;
		exit(0);
	}
}
