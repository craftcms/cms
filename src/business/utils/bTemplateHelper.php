<?php

/**
 *
 */
class bTemplateHelper
{
	public static $globalTags = array(
		'assets'   => 'bAssetsTag',
		'blocks'   => 'bBlocksTag',
		'content'  => 'bContentTag',
		'cp'       => 'bCpTag',
		'date'     => 'bDateTag',
		'url'      => 'bUrlTag',
		'users'    => 'bUsersTag',
		'security' => 'bSecurityTag'
	);

	/**
	 * Returns a new tag instance, which will either be one of the globals, or a generic tag, depending on the handle passed in
	 * @param string $handle The tag handle being used in the template
	 * @return object The tag instance
	 */
	public static function getGlobalTag($handle)
	{
		if (isset(self::$globalTags[$handle]))
			return new self::$globalTags[$handle];

		return new bTag;
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
	 * @return \bArrayTag|\bBoolTag|mixed|\bNumTag|\bObjectTag|string|\bStringTag
	 */
	public static function getVarTag($var = '')
	{
		// if $var is a tag, just return it
		if (self::isTag($var))
			return $var;

		// is it a number?
		if (is_int($var) || is_float($var))
			return new bNumTag($var);

		// is it an array?
		if (is_array($var))
			return new bArrayTag($var);

		// is it a bool?
		if (is_bool($var))
			return new bBoolTag($var);

		// is it an object?
		if (is_object($var))
		{
			return new bObjectTag($var);
		}

		// default to a string
		return new bStringTag($var);
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
	 * @static
	 * @param $templatePath
	 * @param $requestPath
	 * @return bool|string
	 */
	public static function findFileSystemMatch(&$templatePath, &$requestPath)
	{
		$fullMatchPath = false;

		// check the given path+fileExtension.
		$matchPath = Blocks::app()->findLocalizedFile($templatePath.$requestPath.Blocks::app()->viewRenderer->fileExtension);
		if (is_file($matchPath))
			$fullMatchPath = realpath($matchPath);

		if ($fullMatchPath === false)
		{
			// now try to match /path/to/folder/index+fileExtension
			$requestPath = ltrim(Blocks::app()->path->normalizeTrailingSlash($requestPath).'index', '/');
			$templatePath = Blocks::app()->path->normalizeTrailingSlash($templatePath);

			$matchPath = Blocks::app()->findLocalizedFile($templatePath.$requestPath.Blocks::app()->viewRenderer->fileExtension);
			if (is_file($matchPath))
				$fullMatchPath = realpath($matchPath);
		}

		return $fullMatchPath;
	}
}
