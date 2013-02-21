<?php
namespace Blocks;

/**
 * Loads Blocks templates into Twig.
 */
class TemplateLoader implements \Twig_LoaderInterface
{
	/**
	 * Gets the source code of a template.
	 *
	 * @param mixed $template The name of the template to load, or a StringTemplate object
	 * @return string The template source code
	 */
	public function getSource($template)
	{
		if (is_string($template))
		{
			return IOHelper::getFileContents(blx()->templates->findTemplate($template));
		}
		else
		{
			return $template->template;
		}
	}

	/**
	 * Gets the cache key to use for the cache for a given template.
	 *
	 * @param mixed $template The name of the template to load, or a StringTemplate object
	 * @return string The cache key
	 */
	public function getCacheKey($template)
	{
		if (is_string($template))
		{
			return blx()->templates->findTemplate($template);
		}
		else
		{
			return $template->cacheKey;
		}
	}

	/**
	 * Returns whether the cached template is still up-to-date with the latest template.
	 *
	 * @param mixed $template The template name, or a StringTemplate object
	 * @param timestamp $cachedModifiedTime The last modification time of the cached template
	 * @return bool
	 */
	public function isFresh($template, $cachedModifiedTime)
	{
		// If this is a CP request and a DB update is needed, force a recompile.
		if (blx()->request->isCpRequest() && blx()->updates->isBlocksDbUpdateNeeded())
		{
			return false;
		}

		if (is_string($template))
		{
			$sourceModifiedTime = IOHelper::getLastTimeModified(blx()->templates->findTemplate($template));
			return $sourceModifiedTime->getTimestamp() <= $cachedModifiedTime;
		}
		else
		{
			return false;
		}
	}
}
