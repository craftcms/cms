<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig;

use Craft;
use craft\app\helpers\IOHelper;
use craft\app\web\View;

/**
 * Loads Craft templates into Twig.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TemplateLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
{
	// Properties
	// =========================================================================

	/**
	 * @var View
	 */
	protected $view;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __construct(View $view)
	{
		$this->view = $view;
	}

	 /**
	 * Checks if a template exists.
	 *
	 * @param string $name
	  *
	 * @return bool
	 */
	public function exists($name)
	{
		return $this->view->doesTemplateExist($name);
	}

	/**
	 * Gets the source code of a template.
	 *
	 * @param  string $name The name of the template to load, or a StringTemplate object.
	 * @return string The template source code.
	 * @throws TemplateLoaderException if the template doesn’t exist or isn’t readable
	 */
	public function getSource($name)
	{
		if (is_string($name))
		{
			$template = $this->_resolveTemplate($name);

			if (IOHelper::isReadable($template))
			{
				return IOHelper::getFileContents($template);
			}
			else
			{
				throw new TemplateLoaderException($name, Craft::t('app', 'Tried to read the template at {path}, but could not. Check the permissions.', ['path' => $template]));
			}
		}
		else
		{
			return $name->template;
		}
	}

	/**
	 * Gets the cache key to use for the cache for a given template.
	 *
	 * @param string $name The name of the template to load, or a StringTemplate object.
	 * @return string The cache key (the path to the template)
	 * @throws TemplateLoaderException if the template doesn’t exist
	 */
	public function getCacheKey($name)
	{
		if (is_string($name))
		{
			return $this->_resolveTemplate($name);
		}
		else
		{
			return $name->cacheKey;
		}
	}

	/**
	 * Returns whether the cached template is still up-to-date with the latest template.
	 *
	 * @param string $name The template name, or a StringTemplate object.
	 * @param int    $time The last modification time of the cached template
	 * @return boolean
	 * @throws TemplateLoaderException if the template doesn’t exist
	 */
	public function isFresh($name, $time)
	{
		// If this is a CP request and a DB update is needed, force a recompile.
		$request = Craft::$app->getRequest();

		if (!$request->getIsConsoleRequest() && $request->getIsCpRequest() && Craft::$app->getUpdates()->isCraftDbMigrationNeeded())
		{
			return false;
		}

		if (is_string($name))
		{
			$sourceModifiedTime = IOHelper::getLastTimeModified($this->_resolveTemplate($name));
			return $sourceModifiedTime->getTimestamp() <= $time;
		}
		else
		{
			return false;
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the path to a given template, or throws a TemplateLoaderException.
	 *
	 * @param $name
	 * @return string $name
	 * @throws TemplateLoaderException if the template doesn’t exist
	 */
	private function _resolveTemplate($name)
	{
		$template = $this->view->resolveTemplate($name);

		if ($template !== false)
		{
			return $template;
		}
		else
		{
			throw new TemplateLoaderException($name, Craft::t('app', 'Unable to find the template “{template}”.', ['template' => $name]));
		}
	}
}
