<?php
namespace Craft;

/**
 * Class TemplateLoaderException
 *
 * @package craft.app.etc.errors
 */
class TemplateLoaderException extends \Twig_Error_Loader
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

		parent::__construct($message);
	}
}
