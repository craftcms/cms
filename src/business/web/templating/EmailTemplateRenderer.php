<?php
namespace Blocks;

/**
 *
 */
class EmailTemplateRenderer extends TemplateRenderer
{
	/**
	 * Returns the template path, relative to the template root directory
	 * @access protected
	 * @return string
	 */
	protected function getRelativePath()
	{
		return substr($this->_sourceTemplatePath, strlen(b()->path->emailTemplatePath));
	}

	/**
	 * Returns the full path to the duplicate template in the parsed_templates directory
	 * @access protected
	 * @return string
	 */
	protected function getDuplicatePath()
	{
		return b()->path->emailTemplateCachePath.$this->relativePath;
	}
}
