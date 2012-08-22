<?php
namespace Blocks;

/**
 * Loads @@@productDisplay@@@ templates into Twig.
 */
class TemplateLoader extends \Twig_Loader_Filesystem
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Gets the source code of a template.
	 *
	 * @param mixed $template The name of the template to load, or a StringTemplate object
	 * @return string The template source code
	 */
	public function getSource($template)
	{
		if (is_string($template))
			return file_get_contents($this->findTemplate($template));
		else
			return $template->template;
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
			return $this->findTemplate($template);
		else
			return $template->cacheKey;
	}

	/**
	 * Returns true if the template is still fresh.
	 *
	 * @param mixed $template The template name, or a StringTemplate object
	 * @param timestamp $time The last modification time of the cached template
	 */
	public function isFresh($template, $time)
	{
		if (is_string($template))
			return filemtime($this->findTemplate($template)) <= $time;
		else
			return false;
	}

	/**
	 * Finds the template path based on its name.
	 *
	 * @access protected
	 * @param string $name The name of the template to load
	 * @throws TemplateLoaderException
	 * @return string The template path
	 */
	protected function findTemplate($name)
	{
		// Normalize the template name
		$name = trim(preg_replace('#/{2,}#', '/', strtr($name, '\\', '/')), '/');

		// Is this template path already cached?
		if (isset($this->cache[$name]))
			return $this->cache[$name];

		// Validate the template name
		$this->validateName($name);

		// Check if the template exists in the main templates path

		// Set the view path
		//  - We need to set this for each template request, in case it was changed to a plugin's template path
		$basePath = blx()->path->getTemplatesPath();

		if ($path = $this->_findTemplate($basePath.$name))
			return $this->cache[$name] = $path;

		// Otherwise maybe it's a plugin template?

		// Only attempt to match against a plugin's templates if this is a CP or action request.
		if (($mode = blx()->request->getMode()) == RequestMode::CP || $mode == RequestMode::Action)
		{
			$parts = array_filter(explode('/', $name));
			$plugin = strtolower(array_shift($parts));

			if ($plugin && blx()->plugins->getPlugin($plugin))
			{
				// Get the template path for the plugin.
				$basePath = blx()->path->getPluginsPath().$plugin.'/templates/';

				// Chop off the plugin segment, since that's already covered by $basePath
				$name = implode($parts);

				if ($path = $this->_findTemplate($basePath.$name))
					return $this->cache[$name] = $path;
			}
		}

		throw new TemplateLoaderException($name);
	}

	/**
	 * Searches for localized template files, and returns the first match if there is one.
	 *
	 * @access protected
	 * @param string $path
	 * @return mixed
	 */
	protected function _findTemplate($path)
	{
		// Get the extension on the path, if there is one
		$extension = FileHelper::getExtension($path);

		if ($extension)
			$testPaths = array($path);
		else
			$testPaths = array($path.'.html', $path.'/index.html');

		foreach ($testPaths as $path)
		{
			if (is_file(blx()->findLocalizedFile($path)))
				return $path;
		}

		return null;
	}
}
