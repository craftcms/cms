<?php

class BlocksTemplateRenderer extends CApplicationComponent implements IViewRenderer
{	
	private $_sourceTemplatePath;
	private $_parsedTemplatePath;
	private $_destinationMetaPath;
	private $_template;
	private $_variables;

	private static $_filePermission = 0755;

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
			@chmod($this->_parsedTemplatePath, self::$_filePermission);
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
			@mkdir(dirname($this->_parsedTemplatePath), self::$_filePermission, true);
	}

	/**
	 * Returns whether the template needs to be (re-)parsed
	 */
	private function isTemplateParsingNeeded()
	{
		// always re-parse templates if in dev mode
		if (Blocks::app()->config('devMode'))
			return true;

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
		$this->parseVariableTags();
		$this->parseLanguage();

		if ($this->_variables)
		{
			$head = '<?php'.PHP_EOL;

			foreach ($this->_variables as $var)
			{
				$head .= 'if (!isset($' . $var . ')) $' . $var . ' = new Tag;' . PHP_EOL;
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
		$this->_template = preg_replace('/\{\!\-\-.*\-\-\}/m', '', $this->_template);
	}

	/**
	 * Parse actions
	 */
	private function parseActions()
	{
		$this->_template = preg_replace_callback('/\{\%\s*(\w+)(\s+(.+)\s+)?\s*\%\}/Um', array(&$this, 'parseActionMatch'), $this->_template);
	}

	/**
	 * Parse an action match
	 */
	private function parseActionMatch($match)
	{
		$action = $match[1];
		$params = empty($match[3]) ? '' : $match[3];

		switch ($action)
		{
			// {% foreach page.fields as field %}

			case 'foreach':
				if (preg_match('/^(.+)\s+as\s+(.+)$/m', $params, $match))
				{
					$this->parseVariable($match[1]);
					$this->parseVariable($match[2]);
					return '<?php foreach ('.$match[1].'->__toArray() as '.$match[2].'): ?>';
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
	 * Parse variable tags
	 */
	private function parseVariableTags()
	{
		// find any remaining {variable-tags} on the page
		$this->_template = preg_replace_callback('/\{(.*)\}/Um', array(&$this, 'parseVariableTagMatch'), $this->_template);
	}

	/**
	 * Parse a variable tag match
	 */
	private function parseVariableTagMatch($match)
	{
		// make sure this isn't JSON
		if (preg_match('/^\s*\w+\s*:/m', $match[1]))
		{
			return $match[0];
		}

		$this->parseVariables($match[1], true);
		return '<?php echo ' . $match[1] . ' ?>';
	}

	/**
	 * Parse variables
	 */
	private function parseVariables(&$str, $toString = false)
	{
		do {
			$match = $this->parseVariable($str, $offset, $toString);
		} while ($match);
	}

	/**
	 * Parse variable
	 */
	private function parseVariable(&$str, &$offset = 0, $toString = false)
	{
		if (preg_match('/(?<![-\.\'"\w])[A-Za-z][-\w]*/', $str, $tagMatch, PREG_OFFSET_CAPTURE, $offset))
		{
			$tag = $tagMatch[0][0];
			$parsedTag = '$'.$tag;
			$tagLength = strlen($tagMatch[0][0]);
			$tagOffset = $tagMatch[0][1];

			// search for immidiately following subtags
			$substr = substr($str, $tagOffset + $tagLength);

			while (preg_match('/^
				(?P<subtag>
					\s*\.\s*
					(?P<func>[A-Za-z][-\w]*)        # <func>
					(?:\(                           # parentheses (optional)
						(?P<params>                 # <params> (optional)
							(?P<param>              # <param>
								(?P<quote>[\'"])    # <quote>
									.*?
								(?<!\\\)(?P=quote)
								|
								[A-Za-z][-\w]*(?P>subtag)?
							)
							(?P<moreParams>         # <moreParams> (optional)
								\s*\,\s*
								(?P>param)
								(?P>moreParams)?    # recursive <moreParams>
							)?
						)?
					\))?
				)/x', $substr, $subtagMatch))
			{
				if (isset($subtagMatch['params']))
				{
					$this->parseVariables($subtagMatch['params']);
				}
				else
				{
					$subtagMatch['params'] = '';
				}

				$parsedTag .= '->'.$subtagMatch['func'].'('.$subtagMatch['params'].')';

				// chop the subtag match from the substring
				$subtagLength = strlen($subtagMatch[0]);
				$substr = substr($substr, $subtagLength);

				// update the total tag length
				$tagLength += $subtagLength;
			}

			if ($toString)
			{
				$parsedTag .= '->__toString()';
			}

			// replace the tag with the parsed version
			$str = substr($str, 0, $tagOffset) . $parsedTag . $substr;

			// update the offset
			$offset = $tagOffset + strlen($parsedTag);

			// make sure the tag is defined at runtime
			if (!in_array($tag, $this->_variables))
			{
				$this->_variables[] = $tag;
			}

			return true;
		}

		return false;
	}

	/**
	 * Parse language
	 */
	private function parseLanguage()
	{
		
	}
}
