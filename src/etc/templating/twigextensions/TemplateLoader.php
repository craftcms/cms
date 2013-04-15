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
	 * @param  string $template The name of the template to load, or a StringTemplate object.
	 * @return string           The template source code.
	 */
	public function getSource($template)
	{
		if (is_string($template))
		{
			return IOHelper::getFileContents(craft()->templates->findTemplate($template));
		}
		else
		{
			return $template->template;
		}
	}

	/**
	 * Gets the cache key to use for the cache for a given template.
	 *
	 * @param string $template The name of the template to load, or a StringTemplate object.
	 * @return string          The cache key (the path to the template)
	 */
	public function getCacheKey($template)
	{
		if (is_string($template))
		{
			return craft()->templates->findTemplate($template);
		}
		else
		{
			return $template->cacheKey;
		}
	}

	/**
	 * Returns whether the cached template is still up-to-date with the latest template.
	 *
	 * @param string    $template           The template name, or a StringTemplate object.
	 * @param timestamp $cachedModifiedTime The last modification time of the cached template
	 * @return bool
	 */
	public function isFresh($template, $cachedModifiedTime)
	{
		// If this is a CP request and a DB update is needed, force a recompile.
		if (craft()->request->isCpRequest() && craft()->updates->isCraftDbUpdateNeeded())
		{
			return false;
		}

		if (is_string($template))
		{
			$sourceModifiedTime = IOHelper::getLastTimeModified(craft()->templates->findTemplate($template));
			return $sourceModifiedTime->getTimestamp() <= $cachedModifiedTime;
		}
		else
		{
			return false;
		}
	}
}
