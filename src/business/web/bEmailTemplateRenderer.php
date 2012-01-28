<?php

/**
 *
 */
class bEmailTemplateRenderer extends bTemplateRenderer
{
	public $fileExtension = '.html';

	/**
	 * Sets the path to the parsed template
	 * @access protected
	 */
	protected function setParsedTemplatePath()
	{
		// get the relative template path
		$relTemplatePath = substr($this->_sourceTemplatePath, strlen(Blocks::app()->path->emailTemplatePath));

		// set the parsed template path
		$this->_parsedTemplatePath = Blocks::app()->path->emailTemplateCachePath.$relTemplatePath;

		// set the meta path
		$this->_destinationMetaPath = $this->_parsedTemplatePath.'.meta';

		// if the template doesn't already end with '.php', append it to the parsed template path
		if (strtolower(substr($relTemplatePath, -4)) != '.php')
		{
			$this->_parsedTemplatePath .= '.php';
		}

		if(!is_file($this->_parsedTemplatePath))
			@mkdir(dirname($this->_parsedTemplatePath), self::$_filePermission, true);
	}


}
