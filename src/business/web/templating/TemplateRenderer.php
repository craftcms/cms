<?php
namespace Blocks;

/**
 *
 */
class TemplateRenderer extends \CApplicationComponent implements \IViewRenderer
{
	public $fileExtension = '.html';

	protected static $_filePermission = 0755;

	protected $_sourcePath;
	protected $_relativePath;
	protected $_duplicatePath;
	protected $_parsedPath;
	protected $_plugin;

	/**
	 * Renders a template
	 * @param object $context The controller or widget who is rendering the template
	 * @param string $sourcePath Path to the source template
	 * @param array  $variables The variables to be passed to the template
	 * @param bool   $return Whether the rendering result should be returned
	 * @return mixed
	 */
	public function renderFile($context, $sourcePath, $variables, $return)
	{
		$this->setPaths($sourcePath);

		if ($this->isTemplateParsingNeeded())
			$this->parseTemplate();

		return $context->renderInternal($this->_parsedPath, $variables, $return);
	}

	/**
	 * Set the template paths
	 * @param string $sourcePath
	 * @throws Exception
	 * @access protected
	 */
	protected function setPaths($sourcePath)
	{
		if (!is_file($sourcePath) || realpath($sourcePath) === false)
			throw new Exception(Blocks::t('blocks', 'The template "{path}" does not exist.', array('{path}' => $sourcePath)));

		$this->_sourcePath    = $sourcePath;
		$this->_relativePath  = $this->getRelativePath();
		$this->_duplicatePath = $this->getDuplicatePath();
		$this->_parsedPath    = $this->getParsedPath();

		// Create the parsed path folder if it's not there
		$dir = dirname($this->_parsedPath);
		if (!file_exists($dir))
			@mkdir($dir, self::$_filePermission, true);
	}

	/**
	 * Returns the template path, relative to the template root directory
	 * @access protected
	 * @return string
	 */
	protected function getRelativePath()
	{
		if (strpos($this->_sourcePath, '/plugins/') !== false)
		{
			$pathSegs = explode('/', $this->_sourcePath);
			$keyMatch = array_search('plugins', $pathSegs);
			$this->_plugin = $pathSegs[$keyMatch + 1];
			$pathSegs = array_slice($pathSegs, $keyMatch + 3);
			return implode('/', $pathSegs);
		}
		else
		{
			return substr($this->_sourcePath, strlen(b()->path->getTemplatePath()));
		}
	}

	/**
	 * Returns the full path to the duplicate template in the parsed_templates directory
	 * @access protected
	 * @return string
	 */
	protected function getDuplicatePath()
	{
		if ($this->_plugin)
		{
			return b()->path->getParsedPluginTemplatesPath().$this->_plugin.'/'.$this->_relativePath;
		}
		else
		{
			return b()->path->getParsedTemplatesPath().$this->_relativePath;
		}
	}

	/**
	 * Returns the full path to the parsed template
	 * @access protected
	 * @return string
	 */
	protected function getParsedPath()
	{
		return $this->_duplicatePath.'.php';
	}

	/**
	 * Returns whether the template needs to be (re-)parsed
	 * @return bool
	 * @access protected
	 */
	protected function isTemplateParsingNeeded()
	{
		// always re-parse templates if in dev mode
		if (b()->config->devMode)
			return true;

		// if last modified date or source is newer, regen
		if (@filemtime($this->_sourcePath) > @filemtime($this->_duplicatePath))
			return true;

		// if the sizes are different regen
		if (@filesize($this->_sourcePath) !== @filesize($this->_duplicatePath))
			return true;

		// the first two checks should catch 95% of all cases.  for the rest, fall back on comparing the files.
		$sourceFile = fopen($this->_sourcePath, 'rb');
		$metaFile = fopen($this->_duplicatePath, 'rb');

		$parseNeeded = false;
		while (!feof($sourceFile) && !feof($metaFile))
		{
			if(fread($sourceFile, 4096) !== fread($metaFile, 4096))
			{
				$parseNeeded = true;
				break;
			}
		}

		if (feof($sourceFile) !== feof($metaFile))
			$parseNeeded = true;

		fclose($sourceFile);
		fclose($metaFile);

		return $parseNeeded;
	}

	/**
	 * Parses a template
	 * @access protected
	 * @throws TemplateRendererException
	 */
	protected function parseTemplate()
	{
		// Copy the source template to the meta file for comparison on future requests.
		copy($this->_sourcePath, $this->_duplicatePath);

		// Initialize a new template parser and have it parse the template
		$parser = new TemplateParser;
		$template = file_get_contents($this->_sourcePath);

		try
		{
			$parsedTemplate = $parser->parseTemplate($template);
		}
		catch (TemplateParserException $e)
		{
			throw new TemplateRendererException($e->getMessage(), $this->_sourcePath, $e->getLine());
		}

		// Save the parsed template to the parsed path
		file_put_contents($this->_parsedPath, $parsedTemplate);
		@chmod($this->_parsedPath, self::$_filePermission);
	}

}
