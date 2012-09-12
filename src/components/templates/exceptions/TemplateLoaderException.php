<?php
namespace Blocks;

/**
 *
 */
class TemplateLoaderException extends Exception
{
	public $template;

	/**
	 * @param string $template
	 */
	function __construct($template)
	{
		$this->template = $template;
		$message = Blocks::t('Unable to find the template “{template}”.', array('template' => $this->template));
		Blocks::log($message);
		parent::__construct($message, null, null);
	}
}
