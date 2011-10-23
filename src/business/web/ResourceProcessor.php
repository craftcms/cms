<?php

if(isset($_GET) && array_key_exists('resourcePath', $_GET))
{
	$resourceProcessor = new ResourceProcessor();
	$resourceProcessor->pluginHandle = isset($_GET['pluginHandle']) ? $_GET['pluginHandle'] : null;
	$resourceProcessor->relativeResourcePathAndName = isset($_GET['resourcePath']) ? $_GET['resourcePath'] : null;
	$resourceProcessor->parseRelativeResourcePath($resourceProcessor->relativeResourcePathAndName);
	$resourceProcessor->processResourceRequest();
}

class ResourceProcessor
{
	public $pluginHandle;
	public $relativeResourcePath;
	public $relativeResourceName;
	public $relativeResourcePathAndName;

	public function processResourceRequest()
	{
		$resourceFullPath = $this->translateResourcePaths($this->relativeResourcePathAndName);

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
		if($this->pluginHandle !== null)
		{
			return BLOCKS_PLUGINS_PATH.$this->pluginHandle.DIRECTORY_SEPARATOR.$this->relativeResourcePathAndName;
		}
		// blocks resource
		else
		{
			return BLOCKS_RESOURCE_PATH.$this->relativeResourcePathAndName;
		}
	}

	public function parseRelativeResourcePath()
	{
		// if the first char is a '/', then strip it.
		if(strpos($this->relativeResourcePathAndName, '/') == 0)
		{
			$this->relativeResourcePathAndName = ltrim($this->relativeResourcePathAndName, '/');
		}

		$slashCount = substr_count($this->relativeResourcePathAndName, '/');

		// bg.gif
		if ($slashCount == 0)
		{
			$this->relativeResourcePath = null;
			$this->relativeResourceName = $this->relativeResourcePathAndName;
		}
		else
		{
			// dir1/bg.gif
			// dir1/dir2/bg.gif
			if ($slashCount > 0)
			{
				$lastSlashPos = strrpos($this->relativeResourcePathAndName, '/');
				$this->relativeResourceName = substr($this->relativeResourcePathAndName, $lastSlashPos + 1);
				$this->relativeResourcePath = substr($this->relativeResourcePathAndName, 0, $lastSlashPos + 1);
			}
			else
			{
				// error, invalid path.
			}
		}
	}

	public function correctImagePaths($content)
	{
		return preg_replace('/url\((\')??((http(s)?\:\/\/)?.+)(\')?\)/U', 'url($5'.BLOCKS_RESOURCEPROCESSOR_URL.'?resourcePath='.$this->relativeResourcePath.'$2$5)', ''.$content.'');
	}

	public function getMimeTypeByExtension($file)
	{
		$extensions = require(BLOCKS_APP_FRAMEWORK_PATH.'utils'.DIRECTORY_SEPARATOR.'mimeTypes.php');

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
