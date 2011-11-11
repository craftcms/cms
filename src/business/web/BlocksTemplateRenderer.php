<?php

class BlocksTemplateRenderer extends CApplicationComponent implements IViewRenderer
{
	private $_variables;
	private $_destinationMetaPath;
	private $_filePermission = 0755;
	private $_varPattern = '(A-Z|a-z)[-\w]*';

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
		if($this->isTemplateParsingNeeded($sourceTemplatePath, $parsedTemplatePath))
		{
			$this->parseTemplate($sourceTemplatePath, $parsedTemplatePath);
			@chmod($parsedTemplatePath, $this->_filePermission);
		}

		return $context->renderInternal($parsedTemplatePath, $tags, $return);
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
		$parsedTemplatePath = $cacheTemplatePath.$relativePath.'.php';
		$this->_destinationMetaPath = $cacheTemplatePath.$relativePath.'.meta';

		if(!is_file($parsedTemplatePath))
			@mkdir(dirname($parsedTemplatePath), $this->_filePermission, true);

		return $parsedTemplatePath;
	}

	/**
	 * Returns whether the template needs to be (re-)parsed
	 * @param string $sourceTemplatePath Path to the source template
	 */
	private function isTemplateParsingNeeded($sourceTemplatePath)
	{
		// if last modified date or source is newer, regen
		if (@filemtime($sourceTemplatePath) > @filemtime($this->_destinationMetaPath))
			return true;

		// if the sizes are different regen
		if (@filesize($sourceTemplatePath) !== @filesize($this->_destinationMetaPath))
			return true;

		// the first two checks should catch 95% of all cases.  for the rest, fall back on comparing the files.
		$sourceFile = fopen($sourceTemplatePath, 'rb');
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
	 * @param string $sourceTemplatePath Path to the source template
	 * @param string $parsedTemplatePath Path to the parsed template
	 */
	private function parseTemplate($sourceTemplatePath, $parsedTemplatePath)
	{
		// copy the source template to the meta file for comparison on future requests.
		copy($sourceTemplatePath, $this->_destinationMetaPath);

		$sourceTemplate = file_get_contents($sourceTemplatePath);
		$parsedTemplate = $this->parse($sourceTemplate);

		file_put_contents($parsedTemplatePath, $parsedTemplate);
	}

	/**
	 * Parse
	 * @param string $template The template contents to parse
	 */
	private function parse($template)
	{
		$this->_variables = array();

		$this->parseComments($template);
		$this->parseActions($template);
		$this->parseVariables($template);
		$this->parseLanguage($template);

		if ($this->_variables)
		{
			$head = '<?php'.PHP_EOL;

			foreach ($this->_variables as $var)
			{
				$head .= 'if (! isset($' . $var . ')) $' . $var . ' = new Tag();' . PHP_EOL;
			}

			$head .= '?>';

			$template = $head . $template;
		}

		return $template;
	}

	/**
	 * Parse comments
	 */
	private function parseComments(&$template)
	{
		$template = preg_replace('/\{\!\-\-.*\-\-\}/Um', '', $template);
	}

	/**
	 * Parse actions
	 */
	private function parseActions(&$template)
	{
		$template = preg_replace_callback('/\{\%\s*(\w+)(\s+(.+)\s+)?\s*\%\}/Um', array(&$this, '_parseAction'), $template);
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
	private function parseVariables($template)
	{
		return $template;
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
	private function parseLanguage(&$template)
	{
		
	}
}
