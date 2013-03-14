<?php
namespace Craft;

/**
 * Loads Craft templates into Twig.
 */
class TemplateLoader implements \Twig_LoaderInterface
{
	/**
	 * Gets the source code of a template.
	 *
	 * @param string $name The name of the template to load
	 * @return string The template source code
	 */
	public function getSource($name)
	{
		$path = craft()->templates->findTemplate($name);
		return IOHelper::getFileContents($path);
	}

	/**
	 * Gets the cache key to use for the cache for a given template.
	 *
	 * @param string $name The name of the template to load
	 * @return string The cache key (the path to the template)
	 */
	public function getCacheKey($name)
	{
		return craft()->templates->findTemplate($name);
	}

	/**
	 * Returns whether the cached template is still up-to-date with the latest template.
	 *
	 * @param string $name The template name
	 * @param timestamp $cachedModifiedTime The last modification time of the cached template
	 * @return bool
	 */
	public function isFresh($name, $cachedModifiedTime)
	{
		// If this is a CP request and a DB update is needed, force a recompile.
		if (craft()->request->isCpRequest() && craft()->updates->isCraftDbUpdateNeeded())
		{
			return false;
		}

		$path = craft()->templates->findTemplate($name);
		$sourceModifiedTime = IOHelper::getLastTimeModified($path);
		return ($sourceModifiedTime->getTimestamp() <= $cachedModifiedTime);
	}
}
