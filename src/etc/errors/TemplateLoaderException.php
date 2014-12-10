<?php
namespace Craft;

/**
 * Class TemplateLoaderException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.errors
 * @since     1.0
 */
class TemplateLoaderException extends \Twig_Error_Loader
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $template;

	// Public Methods
	// =========================================================================

	/**
	 * @param string $template
	 *
	 * @return TemplateLoaderException
	 */
	public function __construct($template)
	{
		$this->template = $template;
		$message = Craft::t('Unable to find the template “{template}”.', array('template' => $this->template));

		parent::__construct($message);
	}
}
