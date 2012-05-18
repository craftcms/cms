<?php
namespace Blocks;

/**
 *
 */
class TemplateHelper
{
	/**
	 * Returns whether a variable is a template variable or not
	 * @param mixed $var The variable
	 * @return bool Whether it's a template variable or not
	 */
	public static function isVariable($var)
	{
		$isVariable = (is_object($var) && get_class($var) == 'Blocks\Variable');
		return $isVariable;
	}

	/**
	 * Returns the appropriate template variable for a given variable
	 * @param mixed $var The variable
	 * @param object A template variable instance for the variable
	 * @return mixed
	 */
	public static function getVariable($var = '')
	{
		// If $var is already a template variable, just return it
		if (self::isVariable($var))
			return $var;
		else
			return new Variable($var);
	}

	/**
	 * Resolves a template path to an actual template file.
	 * @param $templatePath
	 * @return mixed If a template match was found, returns the $templatePath string, possibly with "/index" appended to it, otherwise false
	 * @static
	 */
	public static function resolveTemplatePath($templatePath)
	{
		// Remove any trailing slashes.
		$templatePath = rtrim($templatePath, '/');

		// Remove any trailing extension.
		if (($extension = pathinfo($templatePath, PATHINFO_EXTENSION)) !== '')
			$templatePath = substr($templatePath, 0, strlen($templatePath) - strlen($extension) - 1);

		// Make a copy of the original.
		$copyTemplatePath = $templatePath;

		// Check to see if it's an email template.
		if (strncmp($templatePath, '///email', 8) === 0)
		{
			$viewPath = b()->path->getEmailTemplatesPath();
			$copyTemplatePath = substr($copyTemplatePath, 9);
		}
		else
		{
			// If they want an absolute template path.
			if (strncmp($templatePath, '//', 2) === 0)
			{
				// Set the template path depending on the type of request mode we're in (path->getTemplatePath() takes care of that.
				$templatePath = b()->path->getTemplatePath();
				b()->setViewPath($templatePath);
				if (is_dir($templatePath.'_layouts/'))
					b()->setLayoutPath($templatePath.'_layouts/');
			}

			// This view path will either be the CP template path or the front-end template path.
			$viewPath = b()->getViewPath();
		}

		// Get the template renderer default file extension.
		$templateExtension = b()->viewRenderer->fileExtension;

		// Check if request/path.ext exists
		if (($matchPath = self::_matchTemplatePathToFileSystem($viewPath.$copyTemplatePath.$templateExtension)) !== false)
			return array('fileSystemPath' => $matchPath, 'templatePath' => $copyTemplatePath);

		// Otherwise check if request/path/index.ext exists
		if (($matchPath = self::_matchTemplatePathToFileSystem($viewPath.$copyTemplatePath.'/index'.$templateExtension)) !== false)
			return array('fileSystemPath' => $matchPath, 'templatePath' => $copyTemplatePath.'/index');

		// Only attempt to match against a plugin's templates if this is a CP request.
		if (b()->request->getMode() == RequestMode::CP)
		{
			// Check to see if the template path might be referring to a plugin template
			$templateSegs = explode('/', $copyTemplatePath);
			if (isset($templateSegs[0]) && $templateSegs[0] !== '')
			{
				if (($plugin = b()->plugins->getPlugin($templateSegs[0])) !== false)
				{
					// Get the template path for the plugin.
					$viewPath = b()->path->getPluginsPath().$plugin->class.'/templates/';

					// If the plugin's templates directory exists, set the request's viewpath to it.
					if (is_dir($viewPath))
					{
						// Set the template path and layout path to the plugin's
						b()->setViewPath($viewPath);
						if (is_dir($viewPath.'_layouts/'))
							b()->setLayoutPath($viewPath.'_layouts/');

						$copyTemplatePath = substr($copyTemplatePath, strlen($plugin->class) + 1);
						$copyTemplatePath = !$copyTemplatePath ? '' : $copyTemplatePath;

						// Check for plugin/request/path.ext
						if (($matchPath = self::_matchTemplatePathToFileSystem($viewPath.$copyTemplatePath.$templateExtension)) !== false)
							return array('fileSystemPath' => $matchPath, 'templatePath' => $copyTemplatePath);

						// Check for plugin/request/path/index.ext
						$copyTemplatePath = $copyTemplatePath == '' ? 'index' : $copyTemplatePath.'/index';
						if (($matchPath = self::_matchTemplatePathToFileSystem($viewPath.$copyTemplatePath.$templateExtension)) !== false)
							return array('fileSystemPath' => $matchPath, 'templatePath' => $copyTemplatePath);
					}
				}
			}
		}

		// No match found.
		return false;
	}

	/**
	 * @param $path
	 * @return bool
	 */
	private function _matchTemplatePathToFileSystem($path)
	{
		if (is_file(b()->findLocalizedFile($path)))
			return $path;
		else
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
