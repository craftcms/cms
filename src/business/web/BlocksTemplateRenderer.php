<?php

class BlocksTemplateRenderer extends CApplicationComponent implements IViewRenderer
{
	private $_sourceTemplatePath;
	private $_parsedTemplatePath;
	private $_destinationMetaPath;
	private $_template;
	private $_phpMarkers;
	private $_phpCode;
	private $_hasLayout;
	private $_variables;

	private static $_filePermission = 0755;

	// TODO: Don't know if it's possible to use conditionally select a template renderer (CViewRenderer for /app/framework and BlocksTemplateRenderer for everything else)
	// so temporarily adding this.
	/**
	 * @var string Only used by CViewRenderer when we're rendering a file from the app/framework directory (as in gii).
	 */
	public $fileExtension = 'php';

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
		// get the relative template path
		$relTemplatePath = substr($this->_sourceTemplatePath, strlen(Blocks::app()->path->getTemplatePath()));

		// set the parsed template path
		$this->_parsedTemplatePath = Blocks::app()->path->getTemplateCachePath().$relTemplatePath;

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

		$this->_phpMarkers = array();
		$this->_phpCode = array();
		$this->_hasLayout = false;
		$this->_variables = array();

		$this->extractPhp();
		$this->parseComments();
		$this->parseActions();
		$this->parseVariableTags();
		$this->parseLanguage();
		$this->restorePhp();
		$this->prependHead();
		$this->appendFoot();

		file_put_contents($this->_parsedTemplatePath, $this->_template);
	}

	/**
	 * Extracts PHP code, replacing it with markers so that we don't risk parsing something in the code that should have been left alone
	 */
	private function extractPhp()
	{
		$this->_template = preg_replace_callback('/\<\?php(.*)\?\>/Ums', array(&$this, 'extractPhpMatch'), $this->_template);
		$this->_template = preg_replace_callback('/\<\?=(.*)\?\>/Ums', array(&$this, 'extractPhpShortTagMatch'), $this->_template);
		$this->_template = preg_replace_callback('/\<\?(.*)\?\>/Ums', array(&$this, 'extractPhpMatch'), $this->_template);
	}

	/**
	 * Extract a PHP code match
	 */
	private function extractPhpMatch($match)
	{
		$code = $match[1];

		// make sure it starts with whitespace
		if (!preg_match('/^\s/', $code))
		{
			$code = ' '.$code;
		}

		$this->_phpCode[] = '<?php'.$code.'?>';
		$marker = $this->_phpMarkers[] = '[PHP:'.count($this->_phpCode).']';

		return $marker;
	}

	/**
	 * Extract a PHP short tag match
	 */
	private function extractPhpShortTagMatch($match)
	{
		$match[1] = 'echo '.$match[1];
		return $this->extractPhpMatch($match);
	}
 
	/**
	 * Restore the PHP code
	 */
	private function restorePhp()
	{
		$this->_template = str_replace($this->_phpMarkers, $this->_phpCode, $this->_template);
	}

	/**
	 * Prepend the PHP head to the template
	 */
	private function prependHead()
	{
		$head = '<?php'.PHP_EOL;

		foreach ($this->_variables as $var)
		{
			$head .= "if (!isset(\${$var})) \${$var} = \$this->getTemplateTag('{$var}');".PHP_EOL;
		}
		
		$head .= '$this->layout = null;'.PHP_EOL;

		if ($this->_hasLayout)
		{
			$head .= '$_layout = $this->beginWidget(\'LayoutTemplateWidget\');'.PHP_EOL;
		}

		$head .= '?>';

		$this->_template = $head . $this->_template;
	}

	/**
	 * Append the PHP foot to the template
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
	 */
	private function parseComments()
	{
		$this->_template = preg_replace('/\{\!\-\-.*\-\-\}/Ums', '', $this->_template);
	}

	/**
	 * Parse actions
	 */
	private function parseActions()
	{
		$this->_template = preg_replace_callback('/\{\%\s*(\/?\w+)(\s+(.+))?\s*\%\}/Um', array(&$this, 'parseActionMatch'), $this->_template);
	}

	/**
	 * Parse an action match
	 */
	private function parseActionMatch($match)
	{
		$action = $match[1];
		$params = isset($match[3]) ? $match[3] : '';

		switch ($action)
		{
			// Layouts, regions, and includes

			case 'layout':
				$this->_hasLayout = true;
				$this->parseVariables($params);
				return "<?php \$_layout->view = {$params}; ?>";

			case 'region':
				$this->_hasLayout = true;
				$regionName = trim($params, '\'"');
				return "<?php \$_layout->regions[] = \$this->beginWidget('RegionTemplateWidget', array('name' => '{$regionName}')); ?>";

			case '/region':
			case 'endregion':
				return '<?php $this->endWidget(); ?>';

			case 'include':
				$this->parseVariables($params);
				return "<?php \$this->loadTemplate({$params}); ?>";

			// Loops

			case 'foreach':
				if (preg_match('/^(.+)\s+as\s+(?:([A-Za-z]\w*)\s*,\s*)?([A-Za-z]\w*)$/m', $params, $match))
				{
					$this->parseVariable($match[1]);
					$as = '$'.$match[3];

					if (!empty($match[2]))
					{
						$as = "\${$match[2]} => {$as}";
						$after = " \${$match[2]} = new NumTag(\${$match[2]});";
					}
					else
					{
						$after = '';
					}

					return "<?php foreach ({$match[1]}->__toArray() as {$as}):{$after} ?>";
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
			case 'elsif':
				$this->parseVariables($params);
				return "<?php elsif ({$params}): ?>";

			case 'else':
				return '<?php else ?>';

			case '/if':
			case 'endif':
				return '<?php endif ?>';

			// Redirect

			case 'redirect':
				$this->parseVariables($params, true);
				return "<?php header('Location: '.{$params}); ?>";
		}
	}

	/**
	 * Parse variable tags
	 */
	private function parseVariableTags()
	{
		// find any remaining {variable-tags} on the page
		$this->_template = preg_replace_callback('/\{\{(.*)\}\}/U', array(&$this, 'parseVariableTagMatch'), $this->_template);
	}

	/**
	 * Parse a variable tag match
	 */
	private function parseVariableTagMatch($match)
	{
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
		if (preg_match('/(?<![-\.\'"\w\/])[A-Za-z]\w*/', $str, $tagMatch, PREG_OFFSET_CAPTURE, $offset))
		{
			$tag = $tagMatch[0][0];
			$parsedTag = '$'.$tag;
			$tagLength = strlen($tagMatch[0][0]);
			$tagOffset = $tagMatch[0][1];

			// search for immediately following subtags
			$substr = substr($str, $tagOffset + $tagLength);

			while (preg_match('/^
				(?P<subtag>
					\s*\.\s*
					(?P<func>[A-Za-z]\w*)        # <func>
					(?:\(                           # parentheses (optional)
						(?P<params>                 # <params> (optional)
							(?P<param>              # <param>
								\d+
								|
								(?P<quote>[\'"])    # <quote>
									.*?
								(?<!\\\)(?P=quote)
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
