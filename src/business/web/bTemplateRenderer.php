<?php

/**
 *
 */
class bTemplateRenderer extends CApplicationComponent implements IViewRenderer
{
	public $fileExtension = '.html';

	private $_sourceTemplatePath;
	private $_parsedTemplatePath;
	private $_destinationMetaPath;
	private $_template;
	private $_markers;
	private $_hasLayout;
	private $_variables;

	private static $_filePermission = 0755;

	/**
	 * Renders a template
	 * @param object $context The controller or widget who is rendering the template
	 * @param string $sourceTemplatePath Path to the source template
	 * @param array  $tags The tags to be passed to the template
	 * @param bool   $return Whether the rendering result should be returned
	 * @return mixed
	 */
	public function renderFile($context, $sourceTemplatePath, $tags, $return)
	{
		$this->_sourceTemplatePath = $sourceTemplatePath;

		if (!is_file($this->_sourceTemplatePath) || realpath($this->_sourceTemplatePath) === false)
			throw new bException(Blocks::t('blocks', 'The template "{path}" does not exist.', array('{path}' => $this->_sourceTemplatePath)));

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
	 * @access private
	 */
	private function setParsedTemplatePath()
	{
		// get the relative template path
		$relTemplatePath = substr($this->_sourceTemplatePath, strlen(Blocks::app()->path->templatePath));

		// set the parsed template path
		$this->_parsedTemplatePath = Blocks::app()->path->templateCachePath.$relTemplatePath;

		// set the meta path
		$this->_destinationMetaPath = $this->_parsedTemplatePath.'.meta';

		// if the template doesn't already end with '.php', append it to the parsed template path
		if (strtolower(substr($relTemplatePath, -4)) != '.php')
		{
			$this->_parsedTemplatePath .= '.php';
		}

		if(!is_file($this->_parsedTemplatePath))
			@mkdir(dirname($this->_parsedTemplatePath), self::$_filePermission, true);
	}

	/**
	 * Returns whether the template needs to be (re-)parsed
	 * @return bool
	 * @access private
	 */
	private function isTemplateParsingNeeded()
	{
		// always re-parse templates if in dev mode
		if (Blocks::app()->getConfig('devMode'))
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
	 * @access private
	 */
	private function parseTemplate()
	{
		// copy the source template to the meta file for comparison on future requests.
		copy($this->_sourceTemplatePath, $this->_destinationMetaPath);

		$this->_template = file_get_contents($this->_sourceTemplatePath);

		$this->_markers = array();
		$this->_hasLayout = false;
		$this->_variables = array();

		$this->extractPhp();
		$this->parseComments();
		$this->parseActions();
		$this->parseVariableTags();
		$this->parseLanguage();
		$this->replaceMarkers();
		$this->prependHead();
		$this->appendFoot();

		file_put_contents($this->_parsedTemplatePath, $this->_template);
	}

	/**
	 * Creates a marker
	 * @param array $match The preg_replace_callback match
	 * @return string The new marker
	 * @access private
	 */
	private function createMarker($match)
	{
		$num = count($this->_markers) + 1;
		$marker = '[MARKER:'.$num.']';
		$this->_markers[$marker] = $match[0];
		return $marker;
	}

	/**
	 * Extracts PHP code, replacing it with markers so that we don't risk parsing something in the code that should have been left alone
	 * @access private
	 */
	private function extractPhp()
	{
		$this->_template = preg_replace_callback('/\<\?php(.*)\?\>/Ums', array(&$this, 'createPhpMarker'), $this->_template);
		$this->_template = preg_replace_callback('/\<\?=(.*)\?\>/Ums', array(&$this, 'createPhpShortTagMarker'), $this->_template);
		$this->_template = preg_replace_callback('/\<\?(.*)\?\>/Ums', array(&$this, 'createPhpMarker'), $this->_template);
	}

	/**
	 * Creates a marker for PHP code
	 * @param array $match The preg_replace_callback match
	 * @return string The new marker
	 * @access private
	 */
	private function createPhpMarker($match)
	{
		$code = $match[1];

		// make sure it starts with whitespace
		if (!preg_match('/^\s/', $code))
		{
			$code = ' '.$code;
		}

		$match[0] = '<?php'.$code.'?>';
		return $this->createMarker($match);
	}

	/**
	 * Creates a marker for PHP code using PHP short tags (<? ... ?>)
	 * @param array $match The preg_replace_callback match
	 * @return string The new marker
	 * @access private
	 */
	private function createPhpShortTagMarker($match)
	{
		$match[1] = 'echo '.$match[1];
		return $this->createPhpMarker($match);
	}

	/**
	 * Extracts Strings, replacing them with markers
	 * @param string &$template The template to extract strings from
	 * @access private
	 */
	private function extractStrings(&$template)
	{
		$template = preg_replace_callback('/([\'"]).*\1/Ums', array(&$this, 'createMarker'), $template);
	}

	/**
	 * Restore any extracted code
	 * @access private
	 */
	private function replaceMarkers()
	{
		$this->_template = str_replace(array_keys($this->_markers), $this->_markers, $this->_template);
	}

	/**
	 * Prepend the PHP head to the template
	 * @access private
	 */
	private function prependHead()
	{
		$head = '<?php'.PHP_EOL;

		foreach ($this->_variables as $var)
		{
			$head .= "if (!isset(\${$var})) \${$var} = bTemplateHelper::getGlobalTag('{$var}');".PHP_EOL;
		}
		
		$head .= '$this->layout = null;'.PHP_EOL;

		if ($this->_hasLayout)
		{
			$head .= '$_layout = $this->beginWidget(\'bLayoutTemplateWidget\');'.PHP_EOL;
		}

		$head .= '?>';

		$this->_template = $head . $this->_template;
	}

	/**
	 * Append the PHP foot to the template
	 * @access private
	 */
	private function appendFoot()
	{
		if ($this->_hasLayout)
		{
			$foot = '<?php $this->endWidget(); ?>'.PHP_EOL;
			$this->_template .= $foot;
		}
	}

	/**
	 * Parse comments
	 * @access private
	 */
	private function parseComments()
	{
		$this->_template = preg_replace('/\{\!\-\-.*\-\-\}/Ums', '', $this->_template);
	}

	/**
	 * Parse actions
	 * @access private
	 */
	private function parseActions()
	{
		$this->_template = preg_replace_callback('/\{\%\s*(\/?\w+)(\s+(.+))?\s*\%\}/Um', array(&$this, 'parseActionMatch'), $this->_template);
	}

	/**
	 * Parse an action match
	 * @param array $match The preg_replace_callback match
	 * @return string The parsed action tag
	 * @access private
	 */
	private function parseActionMatch($match)
	{
		$action = $match[1];
		$params = isset($match[3]) ? $match[3] : '';
		$this->extractStrings($params);

		switch ($action)
		{
			// Layouts, regions, and includes

			case 'layout':
				$this->_hasLayout = true;
				$this->parseVariables($params, true);
				return "<?php \$_layout->template = {$params}; ?>";

			case 'region':
				$this->_hasLayout = true;
				$this->parseVariables($params, true);
				return "<?php \$_layout->regions[] = \$this->beginWidget('bRegionTemplateWidget', array('name' => {$params})); ?>";

			case '/region':
			case 'endregion':
				return '<?php $this->endWidget(); ?>';

			case 'include':
				$this->parseVariables($params, true);
				return "<?php \$this->loadTemplate({$params}); ?>";

			// Loops

			case 'foreach':
				if (preg_match('/^(.+)\s+as\s+(?:([A-Za-z]\w*)\s*=>\s*)?([A-Za-z]\w*)$/m', $params, $match))
				{
					$this->parseVariable($match[1]);
					$index = '$'.(!empty($match[2]) ? $match[2] : 'index');
					$subvar = '$'.$match[3];

					return "<?php foreach ({$match[1]}->__toArray() as {$index} => {$subvar}):" . PHP_EOL .
						"{$index} = bTemplateHelper::getVarTag({$index});" . PHP_EOL .
						"{$subvar} = bTemplateHelper::getVarTag({$subvar}); ?>";
				}
				return '';

			case '/foreach':
			case 'endforeach':
				return '<?php endforeach ?>';

			// Conditionals

			case 'if':
				$this->parseVariables($params, true);
				return "<?php if ({$params}): ?>";

			case 'elseif':
				$this->parseVariables($params, true);
				return "<?php elseif ({$params}): ?>";

			case 'else':
				return '<?php else: ?>';

			case '/if':
			case 'endif':
				return '<?php endif ?>';

			// Redirect
			case 'redirect':
				$this->parseVariables($params, true);
				return "<?php Blocks::app()->request->redirect({$params}); ?>";
		}
	}

	/**
	 * Parse variable tags
	 * @param string $template The template to parse variable tags in
	 * @param bool $partOfString Whether $template is part of a string
	 * @return mixed
	 * @access private
	 */
	private function parseVariableTags()
	{
		// find any {{variable-tags}} on the page
		$this->_template = preg_replace_callback('/\{\{\s*(.+)\s*\}\}/U', array(&$this, 'parseVariableTagMatch'), $this->_template);
	}

	/**
	 * Parse a variable tag match
	 * @param array $match The preg_replace_callback match
	 * @return string The parsed variable tag
	 * @access private
	 */
	private function parseVariableTagMatch($match)
	{
		$this->extractStrings($match[1]);
		$this->parseVariables($match[1], true);
		return "<?php echo {$match[1]} ?>";
	}

	/**
	 * Parse variables
	 * @param string $template The template to parse for variables
	 * @param bool $toString Whether to include "->__toString()" at the end of the parsed variables
	 * @access private
	 */
	private function parseVariables(&$template, $toString = false)
	{
		do {
			$match = $this->parseVariable($template, $offset, $toString);
		} while ($match);
	}

	/**
	 * Parse variable
	 * @param string $template The template to be parsed
	 * @param int $offset The offset to start searching for a variable
	 * @param bool $toString Whether to include "->__toString()" at the end of the parsed variable
	 * @return bool Whether a variable was found and parsed
	 * @access private
	 */
	private function parseVariable(&$template, &$offset = 0, $toString = false)
	{
		if (preg_match('/(?<![-\.\'"\w\/\[])[A-Za-z]\w*/', $template, $tagMatch, PREG_OFFSET_CAPTURE, $offset))
		{
			$tag = $tagMatch[0][0];
			$parsedTag = '$'.$tag;
			$tagLength = strlen($tagMatch[0][0]);
			$tagOffset = $tagMatch[0][1];

			// search for immediately following subtags
			$substr = substr($template, $tagOffset + $tagLength);

			while (preg_match('/^
				(?P<subtag>
					\s*\.\s*
					(?P<func>[A-Za-z]\w*)        # <func>
					(?:\(                           # parentheses (optional)
						(?P<params>                 # <params> (optional)
							(?P<param>              # <param>
								\d+
								|
								\[MARKER:\d+\]
								|
								[A-Za-z]\w*(?P>subtag)?
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
				$parsedTag .= '->_subtag(\''.$subtagMatch['func'].'\'';

				if (isset($subtagMatch['params']))
				{
					$this->parseVariables($subtagMatch['params'], $toString);
					$parsedTag .= ', array('.$subtagMatch['params'].')';
				}

				$parsedTag .= ')';

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
			$template = substr($template, 0, $tagOffset) . $parsedTag . $substr;

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
	 * @access private
	 */
	private function parseLanguage()
	{
		
	}
}
