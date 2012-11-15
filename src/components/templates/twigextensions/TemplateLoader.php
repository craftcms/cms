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
	 * Returns true if the template is still fresh.
	 *
	 * @param mixed $template The template name, or a StringTemplate object
	 * @param timestamp $time The last modification time of the cached template
	 * @return bool
	 */
	public function isFresh($template, $time)
	{
		if (is_string($template))
		{
			return IOHelper::getLastTimeModified(blx()->templates->findTemplate($template)) <= $time;
		}
		else
		{
			return false;
		}
	}
}
