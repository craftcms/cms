<?php

/**
 *
 */
class TemplateMatch
{
	private $_relativePath = null;
	private $_fileName = null;
	private $_fullTemplatePath = null;
	private $_matchRequest = null;
	private $_matchType = null;
	private $_matchExtension = null;

	/**
	 * @access public
	 *
	 * @param $path
	 */
	public function __construct($path)
	{
		$this->_fullTemplatePath = $path;
		$this->init($path);
	}

	/**
	 * @access private
	 *
	 * @param $path
	 */
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

	/**
	 * @access public
	 *
	 * @return null
	 */
	public function getFullTemplatePath()
	{
		return $this->_fullTemplatePath;
	}

	/**
	 * @access public
	 *
	 * @return null
	 */
	public function getFileName()
	{
		return $this->_fileName;
	}

	/**
	 * @access public
	 *
	 * @return null
	 */
	public function getRelativePath()
	{
		return $this->_relativePath;
	}

	/**
	 * @access public
	 *
	 * @return null
	 */
	public function getMatchExtension()
	{
		return $this->_matchExtension;
	}

	/**
	 * @access public
	 *
	 * @param $matchRequest
	 */
	public function setMatchRequest($matchRequest)
	{
		$this->_matchRequest = $matchRequest;
	}

	/**
	 * @access public
	 *
	 * @param $matchType
	 */
	public function setMatchType($matchType)
	{
		$this->_matchType = $matchType;
	}

	/**
	 * @access public
	 *
	 * @param $extension
	 */
	public function setMatchExtension($extension)
	{
		$this->_matchExtension = $extension;
	}

}
