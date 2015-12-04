<?php
namespace Craft;

/**
 * TwigEnvironment class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     2.5
 */
class TwigEnvironment extends \Twig_Environment
{
	// Public Methods
	// =========================================================================

	public function loadTemplate($name, $index = null)
	{
		try
		{
			return parent::loadTemplate($name, $index);
		}
		catch (\Twig_Error $e)
		{
			if (craft()->config->get('suppressTemplateErrors'))
			{
				// Just log it and return an empty template
				craft()->errorHandler->logException($e);

				$twig = craft()->templates->getTwig('Twig_Loader_String');
				return $twig->loadTemplate('');
			}
			else
			{
				throw $e;
			}
		}
	}
}
