<?php

class BlocksTemplateRenderer extends CApplicationComponent implements IViewRenderer
{	
	private $_sourceTemplatePath;
	private $_parsedTemplatePath;
	private $_destinationMetaPath;
	private $_filePermission = 0755;
	private $_template;
	private $_variables;

	/**
	 * Renders a template
	 * @param $context The controller or widget who is rendering the template
	 * @param string $sourceTemplatePath Path to the source template
	 * @param array $tags The tags to be passed to the template
	 * @param bool $return Whether the rendering result should be returned
	 */
	public function renderFile($context, $sourceTemplatePath, $tags, $return)
	{
		$this->_sourceTemplatePath = $sourceTemplatePath;

		if (!is_file($this->_sourceTemplatePath) || realpath($this->_sourceTemplatePath) === false)
			throw new BlocksException(Blocks::t('blocks', 'The template "{path}" does not exist.', array('{path}' => $this->_sourceTemplatePath)));

		$this->setParsedTemplatePath();

		if($this->isTemplateParsingNeeded())
		{
			$this->parseTemplate();
			@chmod($this->_parsedTemplatePath, $this->_filePermission);
		}

		return $context->renderInternal($this->_parsedTemplatePath, $tags, $return);
	}

	/**
	 * Sets the path to the parsed template
	 */
	private function setParsedTemplatePath()
	{
		$cacheTemplatePath = Blocks::app()->path->getTemplateCachePath();

		$relativePath = substr($this->_sourceTemplatePath, strlen(Blocks::app()->path->getTemplatePath()));
		$relativePath = substr($relativePath, 0, strpos($relativePath, '.'));
		$this->_parsedTemplatePath = $cacheTemplatePath.$relativePath.'.php';
		$this->_destinationMetaPath = $cacheTemplatePath.$relativePath.'.meta';

		if(!is_file($this->_parsedTemplatePath))
			@mkdir(dirname($this->_parsedTemplatePath), $this->_filePermission, true);
	}

	/**
	 * Returns whether the template needs to be (re-)parsed
	 */
	private function isTemplateParsingNeeded()
	{
		// if last modified date or source is newer, regen
		if (@filemtime($this->_sourceTemplatePath) > @filemtime($this->_destinationMetaPath))
			return true;

		// if the sizes are different regen
		if (@filesize($this->_sourceTemplatePath) !== @filesize($this->_destinationMetaPath))
			return true;

		// the first two checks should catch 95% of all cases.  for the rest, fall back on comparing the files.
		$sourceFile = fopen($this->_sourceTemplatePath, 'rb');
		$metaFile = fopen($this->_destinationMetaPath, 'rb');

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
	 */
	private function parseTemplate()
	{
		// copy the source template to the meta file for comparison on future requests.
		copy($this->_sourceTemplatePath, $this->_destinationMetaPath);

		$this->_template = file_get_contents($this->_sourceTemplatePath);

		$this->_variables = array();

		$this->parseComments();
		$this->parseActions();
		$this->parseVariables();
		$this->parseLanguage();

		if ($this->_variables)
		{
			$head = '<?php'.PHP_EOL;

			foreach ($this->_variables as $var)
			{
				$head .= 'if (! isset($' . $var . ')) $' . $var . ' = new Tag();' . PHP_EOL;
			}

			$head .= '?>';

			$this->_template = $head . $this->_template;
		}

		file_put_contents($this->_parsedTemplatePath, $this->_template);
	}

	/**
	 * Parse comments
	 */
	private function parseComments()
	{
		$this->_template = preg_replace('/\{\!\-\-.*\-\-\}/Um', '', $this->_template);
	}

	/**
	 * Parse actions
	 */
	private function parseActions()
	{
		$this->_template = preg_replace_callback('/\{\%\s*(\w+)(\s+(.+)\s+)?\s*\%\}/Um', array(&$this, '_parseAction'), $this->_template);
	}

	private function _parseAction($match)
	{
		$action = $match[1];
		$params = empty($match[3]) ? '' : $match[3];

		switch ($action)
		{
			// {% foreach page.fields as field %}

			case 'foreach':
				if (preg_match('/^(\S+)\s+as\s+(\S+)$/m', $params, $match))
				{
					$var = $this->parseVariable($match[1]);
					return '<?php foreach ('.$var.'->__toArray() as $'.$match[2].'): ?>';
				}
				return '';

			case 'endforeach':
				return '<?php endforeach ?>';

			// {% if condition %} [... {% elseif condition %}] [... {% else %}] ... {% endif %}

			case 'if':
				$this->parseVariables($params);
				return '<?php if ('.$params.'): ?>';

			case 'elseif':
			case 'elsif':
				$this->parseVariables($params);
				return '<?php elsif ('.$params.'): ?>';

			case 'else':
				return '<?php else ?>';

			case 'endif':
				return '<?php endif ?>';

			// {% include "path/to/template" %}

			case 'include':
				return '';

			// {% layout "path/to/layout" %}

			case 'layout':
				return '';
		}
	}

	/**
	 * Parse variables
	 */
	private function parseVariables()
	{
		
	}

	/**
	 * Parses a variable
	 */
	private function parseVariable($var)
	{
		$parts = explode('.', $var);

		$first = array_shift($parts);

		if (! in_array($first, $this->_variables))
		{
			$this->_variables[] = $first;
		}

		$parsed = '$'.$first;

		foreach ($parts as $part)
		{
			$parsed .= '->'.$part.'()';
		}

		return $parsed;
	}

	private function _parseVariable($match)
	{
		if (! in_array($match[0], $this->_variables))
		{
			$this->_variables[] = $match[0];
		}

		return '$'.$match[0];
	}

	/**
	 * Parse language
	 */
	private function parseLanguage()
	{
		
	}
}
