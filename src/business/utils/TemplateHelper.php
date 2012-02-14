<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	public static $services = array(
		'assets',
		'config',
		'content',
		'contentBlocks',
		'cp',
		'dashboard',
		'email',
		'plugins',
		'request',
		'session',
		'settings',
		'sites',
		'updates',
		'user',
		'users',
	);

	public static $globalTags = array(
		'blocks'    => 'Blocks\BlocksTag',
		'url'       => 'Blocks\UrlTag',
	);

	/**
	 * Returns a new tag instance, which will either be one of the globals, or a generic tag, depending on the handle passed in
	 * @param string $handle The tag handle being used in the template
	 * @return object The tag instance
	 */
	public static function getGlobalTag($handle)
	{
		if (in_array($handle, self::$services))
			return new ObjectTag(Blocks::app()->$handle);

		if (isset(self::$globalTags[$handle]))
			return new self::$globalTags[$handle];

		return new Tag;
	}

	/**
	 * Returns whether a variable is a tag or not
	 * @param mixed $var The variable
	 * @return bool Whether it's a tag or not
	 */
	public static function isTag($var)
	{
		return (is_object($var) && isset($var->__tag__));
	}

	/**
	 * Returns the appropriate tag for a variable
	 * @param mixed $var The variable
	 * @param object A tag instance for the variable
	 * @return \ArrayTag|\BoolTag|mixed|\NumTag|\ObjectTag|string|\StringTag
	 */
	public static function getVarTag($var = '')
	{
		// if $var is a tag, just return it
		if (self::isTag($var))
			return $var;

		// is it a number?
		if (is_numeric($var))
			return new NumTag($var);

		// is it an array?
		if (is_array($var))
			return new ArrayTag($var);

		// is it a bool?
		if (is_bool($var))
			return new BoolTag($var);

		// is it an object?
		if (is_object($var))
		{
			return new ObjectTag($var);
		}

		// default to a string
		return new StringTag($var);
	}

	/**
	 * Combines and serializes the subtag name and arguments into a unique key
	 * @param string $tag The subtag name
	 * @param array $args The arguments passed to the subtag
	 * @return string The subtag's cache key
	 */
	public static function generateTagCacheKey($tag, $args)
	{
		$cacheKey = $tag;
		if ($args) $cacheKey .= '('.serialize($args).')';

		return $cacheKey;
	}

	/**
	 * Resolves a template path to an actual template file by possibly adding "/index" to it
	 * @param $templatePath
	 * @return mixed If a template match was found, returns the $templatePath string, possibly with "/index" appended to it, otherwise false
	 * @static
	 */
	public static function resolveTemplatePath($templatePath)
	{
		$templatePath = trim($templatePath, '/');

		// Check if request/path.ext exists
		$testTemplatePath = $templatePath.Blocks::app()->viewRenderer->fileExtension;
		if (is_file(Blocks::app()->findLocalizedFile(Blocks::app()->viewPath.$testTemplatePath)))
			return $templatePath;

		// Otherwise check if request/path/index.ext exists
		$templatePath .= '/index';
		$testTemplatePath = $templatePath.Blocks::app()->viewRenderer->fileExtension;
		if (is_file(Blocks::app()->findLocalizedFile(Blocks::app()->viewPath.$testTemplatePath)))
			return $templatePath;

		return false;
	}

	/**
	 * Renames input names so they belong to a namespace
	 * @param string $template The template with the inputs
	 * @param string $namespace The namespace to make inputs belong to
	 * @return string The template with namespaced inputs
	 */
	public static function namespaceInputs($template, $namespace)
	{
		// name= attributes
		$template = preg_replace('/(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1'.$namespace.'[$3]$4$2', $template);

		// id= and for= attributes
		$template = preg_replace('/((id=|for=)(\'|"))([^\'"]+)\3/', '$1'.$namespace.'-$4$3', $template);

		return $template;
	}
}
