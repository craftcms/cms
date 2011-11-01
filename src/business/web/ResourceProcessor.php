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

	public function setPluginHandle($pluginHandle)
	{
		$this->_pluginHandle = $pluginHandle;
	}

	public function getPluginHandle()
	{
		return $this->_pluginHandle;
	}

	public function setRelativeResourcePath($relativeResourcePath)
	{
		$this->_relativeResourcePath = $relativeResourcePath;
	}

	public function getRelativeResourcePath()
	{
		return $this->_relativeResourcePath;
	}

	public function setRelativeResourceName($relativeResourceName)
	{
		$this->_relativeResourceName = $relativeResourceName;
	}

	public function getRelativeResourceName()
	{
		return $this->_relativeResourceName;
	}

	public function setRelativeResourcePathAndName($relativeResourcePathAndName)
	{
		$this->_relativeResourcePathAndName = $relativeResourcePathAndName;
	}

	public function getRelativeResourcePathAndName()
	{
		return $this->_relativeResourcePathAndName;
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

	public function parseRelativeResourcePath()
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

	public function sendResource($resourceFullPath)
	{
		$content = file_get_contents($resourceFullPath);
		$file = Blocks::app()->file->set($resourceFullPath);
		$mimeType = $file->getMimeType();

		if(strpos($mimeType, 'css') > 0)
			$content = $this->correctImagePaths($content);

		$file->send(false, false, $content);
	}
}
