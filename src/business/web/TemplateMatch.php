<?php

class TemplateMatch
{
	private $_relativePath = null;
	private $_fileName = null;
	private $_fullTemplatePath = null;
	private $_matchRequest = null;
	private $_matchType = null;
	private $_matchExtension = null;
	private $_moduleName = null;

	public function __construct($path)
	{
		$this->_fullTemplatePath = $path;
		$this->init($path);
	}

	private function init($path)
	{
		$relativeTemplatePath = '';
		$segments = null;

		$path = Blocks::app()->path->normalizeDirectorySeparators($path);
		$pathSegments = array_merge(array_filter(explode('/', $path)));

		if ($pathSegments)
		{
			$file = $pathSegments[count($pathSegments) - 1];

			for ($counter = 0; $counter < count($pathSegments) - 1; $counter++)
			{
				$relativeTemplatePath .= $pathSegments[$counter];

				if ($counter != count($pathSegments) - 2)
					$relativeTemplatePath .= '/';
			}
		}
		else
		{
			$file = 'index';
		}

		$this->_fileName = $file;
		$this->_relativePath = $relativeTemplatePath;
	}

	public function getFullTemplatePath()
	{
		return $this->_fullTemplatePath;
	}

	public function getFileName()
	{
		return $this->_fileName;
	}

	public function getRelativePath()
	{
		return $this->_relativePath;
	}

	public function getMatchExtension()
	{
		return $this->_matchExtension;
	}

	public function setMatchRequest($matchRequest)
	{
		$this->_matchRequest = $matchRequest;
	}

	public function setMatchType($matchType)
	{
		$this->_matchType = $matchType;
	}

	public function setModuleName($moduleName)
	{
		$this->_moduleName = $moduleName;
	}

	public function getModuleName()
	{
		return $this->_moduleName;
	}

	public function setMatchExtension($extension)
	{
		$this->_matchExtension = $extension;
	}

}
