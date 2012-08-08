<?php
namespace Blocks;

/**
 *
 */
class FileTemplateProcessor extends BaseTemplateProcessor
{
	public $fileExtension = '.html';

	protected static $_filePermission = 0755;

	protected $_sourcePath;
	protected $_relativePath;
	protected $_duplicatePath;
	protected $_parsedPath;
	protected $_plugin;

	/**
	 * @param object $context
	 * @param        $templatePath
	 * @param array  $variables
	 * @param bool   $return
	 *
	 * @return mixed|void
	 */
	public function process($context, $templatePath, $variables, $return)
	{
		// If we find a match to the path on the file system, continue processing.
		if (($fileSystemPath = $this->matchTemplateToFileSystem($templatePath)) !== false)
			return $this->run($context, $fileSystemPath, $variables, $return);

		return false;
	}

	/**
	 * Parses a template
	 * @access protected
	 * @throws TemplateProcessorException
	 */
	protected function parseTemplate()
	{
		// Copy the source template to the meta file for comparison on future requests.
		copy($this->_sourcePath, $this->_duplicatePath);

		// Initialize a new template parser and have it parse the template
		$parser = new TemplateParser();
		$template = file_get_contents($this->_sourcePath);

		try
		{
			$parsedTemplate = $parser->parseTemplate($template);
		}
		catch (TemplateProcessorException $e)
		{
			throw new TemplateProcessorException(Blocks::t('Template Processing Error: {errorMessage}', array('{errorMessage}' => $e->getMessage())), $this->_sourcePath, $e->getLine());
		}

		// Save the parsed template to the parsed path
		file_put_contents($this->_parsedPath, $parsedTemplate);
		@chmod($this->_parsedPath, self::$_filePermission);
	}

	/**
	 * Resolves a template path to an actual template file.
	 * @param $templatePath
	 * @return mixed If a template match was found, returns the $templatePath string, possibly with "/index" appended to it, otherwise false
	 * @static
	 */
	public function matchTemplateToFileSystem($templatePath)
	{
		// Remove any trailing slashes.
		$templatePath = rtrim($templatePath, '/');

		// Get an extension for the path.  Default to .html if it can't find one.
		$extension = TemplateHelper::getExtension($templatePath);

		// If there was an extension supplied in the templatePath, strip it.
		if (pathinfo($templatePath, PATHINFO_EXTENSION) !== '')
			$templatePath = substr($templatePath, 0, strlen($templatePath) - strlen($extension));

		// Make a copy of the original.
		$copyTemplatePath = $templatePath;

		// Check to see if they want an absolute template path.
		if (strncmp($templatePath, '//', 2) === 0)
		{
			// Set the template path depending on the type of request mode we're in (path->getTemplatePath() takes care of that.
			$templatePath = blx()->path->getTemplatePath();
			blx()->setViewPath($templatePath);
			if (is_dir($templatePath.'_layouts/'))
				blx()->setLayoutPath($templatePath.'_layouts/');
		}

		// This view path will either be the CP template path or the front-end template path.
		$viewPath = blx()->getViewPath();

		// Set the file extension on the instance.
		$this->fileExtension = $extension;

		// Check if request/path.ext exists
		if (($matchPath = self::_doesLocalizedTemplateFileExist($viewPath.$copyTemplatePath.$extension)) !== false)
			return $matchPath;

		// Otherwise check if request/path/index.ext exists
		if (($matchPath = self::_doesLocalizedTemplateFileExist($viewPath.$copyTemplatePath.'/index'.$extension)) !== false)
			return $matchPath;

		// Only attempt to match against a plugin's templates if this is a CP or action request.
		if (($mode = blx()->request->getMode()) == RequestMode::CP || $mode == RequestMode::Action)
		{
			// Check to see if the template path might be referring to a plugin template
			$templateSegs = explode('/', $copyTemplatePath);
			if (isset($templateSegs[0]) && $templateSegs[0] !== '')
			{
				if (($plugin = blx()->plugins->getPlugin($templateSegs[0])) !== false)
				{
					// Get the template path for the plugin.
					$viewPath = blx()->path->getPluginsPath().$plugin->class.'/templates/';

					// If the plugin's templates directory exists, set the request's viewpath to it.
					if (is_dir($viewPath))
					{
						// Set the template path and layout path to the plugin's
						blx()->setViewPath($viewPath);
						if (is_dir($viewPath.'_layouts/'))
							blx()->setLayoutPath($viewPath.'_layouts/');

						$copyTemplatePath = substr($copyTemplatePath, strlen($plugin->class) + 1);
						$copyTemplatePath = !$copyTemplatePath ? '' : $copyTemplatePath;

						// Check for plugin/request/path.ext
						if (($matchPath = self::_doesLocalizedTemplateFileExist($viewPath.$copyTemplatePath.$extension)) !== false)
							return $matchPath;

						// Check for plugin/request/path/index.ext
						$copyTemplatePath = $copyTemplatePath == '' ? 'index' : $copyTemplatePath.'/index';
						if (($matchPath = self::_doesLocalizedTemplateFileExist($viewPath.$copyTemplatePath.$extension)) !== false)
							return $matchPath;
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
	private function _doesLocalizedTemplateFileExist($path)
	{
		if (is_file(blx()->findLocalizedFile($path)))
			return $path;
		else
			return false;
	}

}
