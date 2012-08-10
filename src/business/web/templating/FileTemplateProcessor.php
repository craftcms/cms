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
			throw new TemplateProcessorException(Blocks::t(TranslationCategory::TemplateProcessing, 'Template Processing Error: {errorMessage}', array('{errorMessage}' => $e->getMessage())), $this->_sourcePath, $e->getLine());
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
		// Remove any leading/trailing slashes.
		$templatePath = trim($templatePath, '/');

		// Get the extension on the path, if there is one
		$extension = FileHelper::getExtension($templatePath);
		$this->fileExtension = ($extension ? $extension : 'html');

		// Check if the template exists in the main templates path

		// Set the view path
		//  - We need to set this for each template request, in case it was changed to a plugin's template path
		$viewPath = blx()->path->getTemplatePath();
		blx()->setViewPath($viewPath);

		if ($extension)
			$testPaths = array($viewPath.$templatePath);
		else
			$testPaths = array($viewPath.$templatePath.'.html', $viewPath.$templatePath.'/index.html');

		if ($matchPath = $this->_lookForLocalizedTemplateFile($testPaths))
			return $matchPath;

		// Otherwise maybe it's a plugin template?

		//  Only attempt to match against a plugin's templates if this is a CP or action request.
		$mode = blx()->request->getMode();
		if ($mode == RequestMode::CP || $mode == RequestMode::Action)
		{
			$templateSegs = explode('/', $templatePath);
			if (!empty($templateSegs[0]))
			{
				if ($plugin = blx()->plugins->getPlugin($templateSegs[0]))
				{
					// Get the template path for the plugin.
					$viewPath = blx()->path->getPluginsPath().$plugin->class.'/templates/';
					blx()->setViewPath($viewPath);

					// Chop off the plugin class, since that's already covered by $viewPath
					$templatePath = substr($templatePath, strlen($plugin->class) + 1);

					if ($extension)
						$testPaths = array($viewPath.$templatePath);
					else
						$testPaths = array($viewPath.$templatePath.'.html', $viewPath.$templatePath.'/index.html');

					if ($matchPath = $this->_lookForLocalizedTemplateFile($testPaths))
						return $matchPath;
				}
			}
		}

		// No match found.
		return false;
	}

	/**
	 * Searches for localized template files, and returns the first match if there is one.
	 *
	 * @param array $paths
	 * @return mixed
	 */
	private function _lookForLocalizedTemplateFile($paths)
	{
		foreach ($paths as $path)
		{
			if (is_file(blx()->findLocalizedFile($path)))
				return $path;
		}	

		return null;
	}

}
