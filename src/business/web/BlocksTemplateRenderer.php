<?php

class BlocksTemplateRenderer extends CApplicationComponent implements IViewRenderer
{
	private $_input;
	private $_output;
	private $_sourceTemplatePath;
	private $_filePermission = 0755;

	/**
	 * Renders a template
	 * @param $context The controller or widget who is rendering the template
	 * @param string $sourceTemplatePath Path to the source template
	 * @param array $tags The tags to be passed to the template
	 * @param bool $return Whether the rendering result should be returned
	 */
	public function renderFile($context, $sourceTemplatePath, $tags, $return)
	{
		if (!is_file($sourceTemplatePath) || realpath($sourceTemplatePath) === false)
			throw new BlocksException(Blocks::t('blocks', 'The template "{path}" does not exist.', array('{path}' => $sourceTemplatePath)));

		$parsedTemplatePath = $this->getParsedTemplatePath($sourceTemplatePath);
		if($this->isParseTemplateNeeded($sourceTemplatePath, $parsedTemplatePath))
		{
			$this->parseTemplate($sourceTemplatePath, $parsedTemplatePath);
			@chmod($parsedTemplatePath, $this->_filePermission);
		}

		return $context->renderInternal($parsedTemplatePath, $tags, $return);
	}

	/**
	 * Parses a template
	 * @param string $sourceTemplatePath Path to the source template
	 * @param string $parsedTemplatePath Path to the parsed template
	 */
	private function parseTemplate($sourceTemplatePath, $parsedTemplatePath)
	{
		$this->_sourceTemplatePath = $sourceTemplatePath;
		$this->_input = file_get_contents($sourceTemplatePath);
		$this->_output = "<?php /* source file: {$sourceTemplatePath} */ ?>".PHP_EOL;

		$this->_output .= $this->_input;
		// when we're ready to actually parse the template, uncomment.
		//$this->parse(0, strlen($this->_input));
		file_put_contents($parsedTemplatePath, $this->_output);
	}

	/**
	 * Returns the path to the parsed template
	 * @param string $sourceTemplatePath Path to the source template
	 */
	private function getParsedTemplatePath($sourceTemplatePath)
	{
		$cacheTemplatePath = Blocks::app()->path->getTemplateCachePath();

		$relativePath = substr($sourceTemplatePath, strlen(Blocks::app()->path->getTemplatePath()));
		$relativePath = substr($relativePath, 0, strpos($relativePath, '.'));
		$cacheTemplatePath = $cacheTemplatePath.$relativePath.'.php';

		if(!is_file($cacheTemplatePath))
			@mkdir(dirname($cacheTemplatePath), $this->_filePermission, true);

		return $cacheTemplatePath;
	}

	/**
	 * Returns whether the template needs to be (re-)parsed
	 * @param string $sourceTemplatePath Path to the source template
	 * @param string $parsedTemplatePath Path to the parsed template
	 */
	private function isParseTemplateNeeded($sourceTemplatePath, $parsedTemplatePath)
	{
		// if last modified date or source is newer, regen
		if (@filemtime($sourceTemplatePath) > @filemtime($parsedTemplatePath))
			return true;

		// if the sizes are different regen
		if (@filesize($sourceTemplatePath) !== @filesize($parsedTemplatePath))
			return true;

		// the first two checks should catch 95% of all cases.  for the rest, fall back on comparing the files.
		$sourceFile = fopen($sourceTemplatePath, 'rb');
		$parsedFile = fopen($parsedTemplatePath, 'rb');

		$parseNeeded = false;
		while (!feof($sourceFile) && !feof($parsedFile))
		{
			if(fread($sourceFile, 4096) !== fread($parsedFile, 4096))
			{
				$parseNeeded = true;
				break;
			}
		}

		if (feof($sourceFile) !== feof($parsedFile))
			$parseNeeded = true;

		fclose($sourceFile);
		fclose($parsedFile);

		return $parseNeeded;
	}

}
