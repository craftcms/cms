<?php
namespace Blocks;

/**
 *
 */
class TemplateRenderer extends BaseComponent implements \IViewRenderer
{
	public $fileExtension = '.html';

	protected $_sourceTemplatePath;
	protected $_parsedTemplatePath;
	protected $_destinationMetaPath;
	protected $_template;
	protected $_markers;
	protected $_hasLayout;
	protected $_variables;

	protected static $_filePermission = 0755;

	const stringPattern = '\[MARKER:\d+\]';
	const tagPattern = '(?<![-\.\'"\w\/\[])[A-Za-z]\w*';
	const subtagPattern = '(?P<subtag>
		\.
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
					[\t ]*\,[\t ]*
					(?P>param)
					(?P>moreParams)?    # recursive <moreParams>
				)?
			)?
		\))?
	)';

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
			throw new Exception(Blocks::t('blocks', 'The template "{path}" does not exist.', array('{path}' => $this->_sourceTemplatePath)));

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
	 * @access protected
	 */
	protected function setParsedTemplatePath()
	{
		// get the relative template path
		$relTemplatePath = substr($this->_sourceTemplatePath, strlen(b()->path->templatePath));

		// set the parsed template path
		$this->_parsedTemplatePath = b()->path->siteTemplateCachePath.$relTemplatePath;

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
	 * @access protected
	 */
	protected function isTemplateParsingNeeded()
	{
		// always re-parse templates if in dev mode
		if (b()->config->getItem('devMode'))
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
	 * Throws a parse error exception
	 * @param string $message The exception message
	 */
	protected function throwParseException($message)
	{
		throw new TemplateRendererException($message, $this->_sourceTemplatePath);
	}

	/**
	 * Parses a template
	 * @access protected
	 */
	protected function parseTemplate()
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
		$this->unescapeCurlyBrackets();
		$this->replaceMarkers();
		$this->prependHead();
		$this->appendFoot();

		file_put_contents($this->_parsedTemplatePath, $this->_template);
	}

	/**
	 * Creates a marker
	 * @param array $match The preg_replace_callback match
	 * @return string The new marker
	 * @access protected
	 */
	protected function createMarker($match)
	{
		$num = count($this->_markers) + 1;
		$marker = '[MARKER:'.$num.']';
		$this->_markers[$marker] = $match[0];
		return $marker;
	}

	/**
	 * Extracts PHP code, replacing it with markers so that we don't risk parsing something in the code that should have been left alone
	 * @access protected
	 */
	protected function extractPhp()
	{
		$this->_template = preg_replace_callback('/\<\?php(.*)\?\>/Ums', array(&$this, 'createPhpMarker'), $this->_template);
		$this->_template = preg_replace_callback('/\<\?=(.*)\?\>/Ums', array(&$this, 'createPhpShortTagMarker'), $this->_template);
		$this->_template = preg_replace_callback('/\<\?(.*)\?\>/Ums', array(&$this, 'createPhpMarker'), $this->_template);
	}

	/**
	 * Creates a marker for PHP code
	 * @param array $match The preg_replace_callback match
	 * @return string The new marker
	 * @access protected
	 */
	protected function createPhpMarker($match)
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
	 * @access protected
	 */
	protected function createPhpShortTagMarker($match)
	{
		$match[1] = 'echo '.$match[1];
		return $this->createPhpMarker($match);
	}

	/**
	 * Extracts Strings, replacing them with markers
	 * @param string &$template The template to extract strings from
	 * @access protected
	 */
	protected function extractStrings(&$template)
	{
		$template = preg_replace_callback('/([\'"]).*\1/Ums', array(&$this, 'createMarker'), $template);
	}

	/**
	 * Restore any extracted code
	 * @access protected
	 */
	protected function replaceMarkers()
	{
		$this->_template = str_replace(array_keys($this->_markers), $this->_markers, $this->_template);
	}

	/**
	 * Prepend the PHP head to the template
	 * @access protected
	 */
	protected function prependHead()
	{
		$head = '<?php'.PHP_EOL
		      . 'namespace Blocks;'.PHP_EOL;

		foreach ($this->_variables as $var)
		{
			$head .= "if (!isset(\${$var})) \${$var} = TemplateHelper::getGlobalTag('{$var}');".PHP_EOL;
		}
		
		$head .= '$this->layout = null;'.PHP_EOL;

		if ($this->_hasLayout)
		{
			$head .= '$_layout = $this->beginWidget(\'Blocks\\LayoutTemplateWidget\');'.PHP_EOL;
		}

		$head .= '?>';

		$this->_template = $head . $this->_template;
	}

	/**
	 * Append the PHP foot to the template
	 * @access protected
	 */
	protected function appendFoot()
	{
		if ($this->_hasLayout)
		{
			$foot = '<?php $this->endWidget(); ?>'.PHP_EOL;
			$this->_template .= $foot;
		}
	}

	/**
	 * Parse comments
	 * @access protected
	 */
	protected function parseComments()
	{
		$this->_template = preg_replace('/\{\!\-\-.*\-\-\}/Ums', '', $this->_template);
	}

	/**
	 * Parse actions
	 * @access protected
	 */
	protected function parseActions()
	{
		$this->_template = preg_replace_callback('/\{\%\s*(\/?\w+)(\s+(.+))?\s*\%\}/Um', array(&$this, 'parseActionMatch'), $this->_template);
	}

	/**
	 * Parse an action match
	 * @param array $match The preg_replace_callback match
	 * @return string The parsed action tag
	 * @access protected
	 */
	protected function parseActionMatch($match)
	{
		$tag = $match[0];
		$action = $match[1];
		$params = isset($match[3]) ? $match[3] : '';
		$this->extractStrings($params);

		switch ($action)
		{
			// Layouts, regions, and includes

			case 'layout':
				if (!preg_match('/^('.self::stringPattern.'|'.self::tagPattern.self::subtagPattern.'?)(\s+.*)?$/x', $params, $match))
					$this->throwParseException("Invalid layout tag “{$tag}”");

				$template = $match[1];
				$params = isset($match[7]) ? trim($match[7]) : '';
				$this->parseVariable($template, $offset, true);
				$this->_hasLayout = true;
				$r = '<?php'.PHP_EOL."\$_layout->template = {$template};".PHP_EOL;

				$params = $this->parseParams($params);
				foreach ($params as $paramName => $paramValue)
				{
					$r .= "\$_layout->tags['{$paramName}'] = {$paramValue};".PHP_EOL;
				}

				$r .= '?>';
				return $r;

			case 'region':
				$this->_hasLayout = true;
				$this->parseVariables($params, true);
				return "<?php \$_layout->regions[] = \$this->beginWidget('Blocks\\RegionTemplateWidget', array('name' => {$params})); ?>";

			case '/region':
			case 'endregion':
				return '<?php $this->endWidget(); ?>';

			case 'include':
				if (!preg_match('/^('.self::stringPattern.'|'.self::tagPattern.self::subtagPattern.'?)(\s+.*)?$/x', $params, $match))
					$this->throwParseException("Invalid include tag “{$tag}”");

				$template = $match[1];
				$params = isset($match[7]) ? trim($match[7]) : '';
				$this->parseVariable($template, $offset, true);
				$r = "<?php \$this->loadTemplate({$template}";
				$params = $this->parseParams($params);
				if ($params)
				{
					$strParams = array();
					foreach ($params as $paramName => $paramValue)
					{
						$strParams[] = "'{$paramName}' => {$paramValue}";
					}
					$r .= ', array('.implode(', ', $strParams).')';
				}
				$r .= '); ?>';
				return $r;

			// Loops

			case 'foreach':
				if (preg_match('/^(.+)\s+as\s+(?:([A-Za-z]\w*)\s*=>\s*)?([A-Za-z]\w*)$/m', $params, $match))
				{
					$this->parseVariable($match[1]);
					$index = '$'.(!empty($match[2]) ? $match[2] : 'index');
					$subvar = '$'.$match[3];

					return "<?php foreach ({$match[1]}->__toArray() as {$index} => {$subvar}):" . PHP_EOL .
						"{$index} = TemplateHelper::getVarTag({$index});" . PHP_EOL .
						"{$subvar} = TemplateHelper::getVarTag({$subvar}); ?>";
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

			// Set
			case 'set':
				if (preg_match('/^([A-Za-z]\w*)\s*=\s*(.*)$/m', $params, $match))
				{
					$this->parseVariables($match[2]);
					return "<?php \${$match[1]} = TemplateHelper::getVarTag({$match[2]}); ?>";
				}
				return '';

			// Redirect
			case 'redirect':
				$this->parseVariables($params, true);
				return "<?php b()->request->redirect(UrlHelper::generateUrl({$params})); ?>";
		}
	}

	/**
	 * Parse variable tags
	 * @return mixed
	 * @access protected
	 */
	protected function parseVariableTags()
	{
		// find any {{variable-tags}} on the page
		$this->_template = preg_replace_callback('/(?<!\\\)\{[\t ]*(.+)[\t ]*(?<!\\\)\}/U', array(&$this, 'parseVariableTagMatch'), $this->_template);
	}

	/**
	 * Parse a variable tag match
	 * @param array $match The preg_replace_callback match
	 * @return string The parsed variable tag
	 * @access protected
	 */
	protected function parseVariableTagMatch($match)
	{
		$this->extractStrings($match[1]);
		$this->parseVariables($match[1], true);
		return "<?php echo {$match[1]} ?>";
	}

	/**
	 * Parse variables
	 * @param string $template The template to parse for variables
	 * @param bool $toString Whether to include "->__toString()" at the end of the parsed variables
	 * @access protected
	 */
	protected function parseVariables(&$template, $toString = false)
	{
		do {
			$match = $this->parseVariable($template, $offset, $toString);
		} while ($match);
	}

	/**
	 * Parse parameters
	 * @param string $template The parameter template chunk
	 * @return string The parsed parameters as PHP array code
	 */
	protected function parseParams($template)
	{
		$params = array();
		$template = trim($template);

		while ($template) {
			$nextEq = strpos($template, '=');

			if ($nextEq === false)
				$this->throwParseException("Invalid parameter “{$template}”");

			if ($nextEq === 0)
				$this->throwParseException('Invalid parameter “”');

			$paramName = rtrim(substr($template, 0, $nextEq));

			if (!preg_match('/^'.self::tagPattern.'$/', $paramName))
				$this->throwParseException('Invalid parameter “'.$paramName.'”');

			$remainingTemplate = ltrim(substr($template, $nextEq+1));

			if (!$remainingTemplate)
				$this->throwParseException("No value set for the parameter “{$paramName}”");

			$recurringSubtagPattern = substr(self::subtagPattern, 0, -1).'(?P>subtag)?)';
			if (!preg_match('/^('.self::stringPattern.'|\d*\.?\d+|'.self::tagPattern.$recurringSubtagPattern.'?)(\s+|$)/x', $remainingTemplate, $match))
				{
					$this->throwParseException("Invalid value set for the parameter “{$paramName}”");}

			$paramValueLength = strlen($match[0]);
			$paramValue = rtrim(substr($remainingTemplate, 0, $paramValueLength));
			$template = substr($remainingTemplate, $paramValueLength);

			$this->parseVariable($paramValue);

			$params[$paramName] = $paramValue;

		}

		return $params;
	}

	/**
	 * Parse variable
	 * @param string $template The template to be parsed
	 * @param int $offset The offset to start searching for a variable
	 * @param bool $toString Whether to include "->__toString()" at the end of the parsed variable
	 * @return bool Whether a variable was found and parsed
	 * @access protected
	 */
	protected function parseVariable(&$template, &$offset = 0, $toString = false)
	{
		if (preg_match('/'.self::tagPattern.'/', $template, $tagMatch, PREG_OFFSET_CAPTURE, $offset))
		{
			$tag = $tagMatch[0][0];
			$parsedTag = '$'.$tag;
			$tagLength = strlen($tagMatch[0][0]);
			$tagOffset = $tagMatch[0][1];

			// search for immediately following subtags
			$substr = substr($template, $tagOffset + $tagLength);

			while (preg_match('/^'.self::subtagPattern.'/x', $substr, $subtagMatch))
			{
				$parsedTag .= '->_subtag(\''.$subtagMatch['func'].'\'';

				if (isset($subtagMatch['params']))
				{
					$this->parseVariables($subtagMatch['params'], true);
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
	 * @access protected
	 */
	protected function parseLanguage()
	{
		
	}

	/**
	 * Unescape any escaped curly brackets
	 * @return mixed
	 * @access protected
	 */
	protected function unescapeCurlyBrackets()
	{
		$this->_template = str_replace(array('\{', '\}'), array('{', '}'), $this->_template);
	}
}
