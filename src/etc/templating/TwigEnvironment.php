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
	/**
	 * @var
	 */
	protected $safeMode;

	// Public Methods
	// =========================================================================

	/**
	 * TwigEnvironment constructor.
	 *
	 * @param Twig_LoaderInterface $loader
	 * @param array                $options\
	 */
	public function __construct(\Twig_LoaderInterface $loader, array $options)
	{
		$options = array_merge(array(
			'safe_mode' => false,
		), $options);

		$this->safeMode = $options['safe_mode'];

		parent::__construct($loader, $options);
	}

	/**
	 * @return mixed
	 */
	public function isSafeMode()
	{
		return $this->safeMode;
	}

	/**
	 * @param $safeMode
	 */
	public function setSafeMode($safeMode)
	{
		$this->safeMode = $safeMode;
	}

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
