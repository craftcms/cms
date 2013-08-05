<?php
namespace Craft;

/**
 * Loads Craft templates into Twig.
 */
class TemplateLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
{
	 /**
     * Checks if a template exists.
     *
     * @param string $name
     * @return bool
     */
    public function exists($name)
    {
    	try
    	{
    		craft()->templates->findTemplate($name);
    		return true;
    	}
    	catch (TemplateLoaderException $e)
    	{
    		return false;
    	}
    }

	/**
	 * Gets the source code of a template.
	 *
	 * @param  string $name The name of the template to load, or a StringTemplate object.
	 * @return string       The template source code.
	 */
	public function getSource($name)
	{
		if (is_string($name))
		{
			return IOHelper::getFileContents(craft()->templates->findTemplate($name));
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
	 * @return string      The cache key (the path to the template)
	 */
	public function getCacheKey($name)
	{
		if (is_string($name))
		{
			return craft()->templates->findTemplate($name);
		}
		else
		{
			return $name->cacheKey;
		}
	}

	/**
	 * Returns whether the cached template is still up-to-date with the latest template.
	 *
	 * @param string    $name The template name, or a StringTemplate object.
	 * @param timestamp $time The last modification time of the cached template
	 * @return bool
	 */
	public function isFresh($name, $time)
	{
		// If this is a CP request and a DB update is needed, force a recompile.
		if (craft()->request->isCpRequest() && craft()->updates->isCraftDbUpdateNeeded())
		{
			return false;
		}

		if (is_string($name))
		{
			$sourceModifiedTime = IOHelper::getLastTimeModified(craft()->templates->findTemplate($name));
			return $sourceModifiedTime->getTimestamp() <= $time;
		}
		else
		{
			return false;
		}
	}
}
