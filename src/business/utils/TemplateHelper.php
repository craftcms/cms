<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	/**
	 * @var array
	 */
	public static $services = array(
		'assets',
		'blocks',
		'config',
		'content',
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
		'request',
		'security',
	);

	/**
	 * @var array
	 */
	public static $globalTags = array(
		'app'               => 'Blocks\AppTag',
		'url'               => 'Blocks\UrlTag',
		'configHelper'      => 'Blocks\ConfigHelperTag',
		'date'              => 'Blocks\DateTag',
		'dateTimeHelper'    => 'Blocks\DateTimeHelperTag',
	);

	/**
	 * Returns a new tag instance, which will either be one of the globals, or a generic tag, depending on the handle passed in
	 * @param string $handle The tag handle being used in the template
	 * @return object The tag instance
	 */
	public static function getGlobalTag($handle)
	{
		if (in_array($handle, self::$services))
		{
			return new Tag(b()->$handle);
		}
		else if (isset(self::$globalTags[$handle]))
		{
			$obj = new self::$globalTags[$handle];
			return new Tag($obj);
		}
		else
			return new Tag;
	}

	/**
	 * Returns whether a variable is a tag or not
	 * @param mixed $var The variable
	 * @return bool Whether it's a tag or not
	 */
	public static function isTag($var)
	{
		$isTag = (is_object($var) && get_class($var) == 'Blocks\Tag');
		if ($isTag) die(get_class($var));
		return $isTag;
	}

	/**
	 * Returns the appropriate tag for a variable
	 * @param mixed $var The variable
	 * @param object A tag instance for the variable
	 * @return ArrayTag|BoolTag|mixed|NumTag|ObjectTag|string|StringTag
	 */
	public static function getTag($var = '')
	{
		// If $var is already a tag, just return it
		if (self::isTag($var))
			return $var;
		else
			return new Tag($var);
	}

	/**
	 * Resolves a template path to an actual template file by possibly adding "/index" to it
	 * @param $templatePath
	 * @return mixed If a template match was found, returns the $templatePath string, possibly with "/index" appended to it, otherwise false
	 * @static
	 */
	public static function resolveTemplatePath($templatePath)
	{
		$copyTemplatePath = $templatePath;

		if (strncmp($templatePath,'///email', 8) === 0)
		{
			$viewPath = b()->path->emailTemplatePath;
			$templatePath = substr($templatePath, 9);
		}
		else
		{
			$viewPath = b()->viewPath;
			$copyTemplatePath = trim($templatePath, '/');
		}

		// Check if request/path.ext exists
		$testTemplatePath = $templatePath.b()->viewRenderer->fileExtension;
		if (is_file(b()->findLocalizedFile($viewPath.$testTemplatePath)))
			return $copyTemplatePath;

		// Otherwise check if request/path/index.ext exists
		$copyTemplatePath .= '/index';
		$testTemplatePath = $copyTemplatePath.b()->viewRenderer->fileExtension;
		if (is_file(b()->findLocalizedFile($viewPath.$testTemplatePath)))
			return $copyTemplatePath;

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
