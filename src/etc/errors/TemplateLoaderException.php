<?php
namespace Craft;

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
		$message = Craft::t('Unable to find the template “{template}”.', array('template' => $this->template));
		Craft::log($message, LogLevel::Error);

		parent::__construct($message, null, null);
	}
}
