<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\errors;

use craft\app\Craft;

/**
 * Class TemplateLoaderException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
