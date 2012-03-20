<?php
namespace Blocks;

/**
 *
 */
class TemplateParser
{
	protected $_template;
	protected $_markers;
	protected $_hasLayout;
	protected $_variables;

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
	 * Parses a template
	 * @param string $template The template to parse
	 * @return string The parsed template
	 */
	public function parseTemplate($template)
	{
		$this->_template = $template;

		$this->_markers = array();
		$this->_hasLayout = false;
		$this->_variables = array();

		$this->extractPhp();
		$this->parseComments();
		$this->parseTags();
		$this->parseEchoShortTags();
		$this->parseLanguage();
		$this->unescapeCurlyBrackets();
		$this->replaceMarkers();
		$this->prependHead();
		$this->appendFoot();

		return $this->_template;
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
		      . 'namespace Blocks;'.PHP_EOL.PHP_EOL;

		if ($this->_variables)
		{
			$head .= '// Predefine all vars used in this template to avoid "Undefined variable" errors.'.PHP_EOL;
			foreach ($this->_variables as $var)
			{
				$head .= "if (!isset(\${$var})) \${$var} = TemplateHelper::getGlobalTag('{$var}');".PHP_EOL;
			}
			$head .= PHP_EOL;
		}

		$head .= '// We\'re not using traditional Yii layouts, since there can only be one of those.'.PHP_EOL
		       . '$this->layout = null;'.PHP_EOL;

		if ($this->_hasLayout)
		{
			$head .= '$_layout = $this->beginWidget(\'Blocks\\LayoutTemplateWidget\');'.PHP_EOL;
		}

		$head .= PHP_EOL.'?>';

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
	 * Parse tags
	 * @access protected
	 */
	protected function parseTags()
	{
		$this->_template = preg_replace_callback('/(?<!\\\)\{(\/?\w+)(?:\s+(.+)\s*)?(?<!\\\)\}/Um', array(&$this, 'parseTagMatch'), $this->_template);
	}

	/**
	 * Parse an action match
	 * @param array $match The preg_replace_callback match
	 * @return string The parsed tag
	 * @access protected
	 */
	protected function parseTagMatch($match)
	{
		$tag = $match[0];
		$type = $match[1];
		$body = isset($match[2]) ? $match[2] : '';
		$this->extractStrings($body);

		// Normalize '/' shortcut
		if (strncmp($type, '/', 1) == 0)
			$type = 'end'.substr($type, 1);

		// Forward it to the proper parse function
		$func = 'parse'.ucfirst($type).'Tag';
		if (method_exists($this, $func))
		{
			try
			{
				return $this->$func($body);
			}
			catch(Exception $e)
			{
				// Tack on the full tag and rethrow
				$message = $e->getMessage().' “'.$tag.'”';

				$numMatch = 0;
				// Try to find the line that this is occurring
				$lines = preg_split('/[\r\n]/', $this->_template);
				foreach ($lines as $num => $line)
				{
					if (strpos($line, $tag) !== false)
					{
						$numMatch = $num + 1;
						break;
					}
				}

				throw new TemplateParserException($message, $numMatch);
			}
		}
		else
			return $tag;
	}

	/**
	 * Parse echo short tags
	 * @return mixed
	 * @access protected
	 */
	protected function parseEchoShortTags()
	{
		$this->_template = preg_replace_callback('/(?<!\\\)\{:\s*(.+)\s*(?<!\\\)\}/Um', array(&$this, 'parseEchoShortTagMatch'), $this->_template);
	}

	/**
	 * Parse an echo short tag match
	 * @param array $match The preg_replace_callback match
	 * @return string The parsed tag
	 * @access protected
	 */
	protected function parseEchoShortTagMatch($match)
	{
		$body = $match[1];
		$this->extractStrings($body);
		return $this->parseEchoTag($body);
	}

	/**
	 * Parses a 'layout' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseLayoutTag($body)
	{
		if (!preg_match('/^('.self::stringPattern.'|'.self::tagPattern.self::subtagPattern.'?)(\s+.*)?$/x', $body, $match))
			throw new Exception('Invalid layout tag');

		$template = $match[1];
		$body = isset($match[7]) ? trim($match[7]) : '';
		$this->parseVariable($template, $offset, true);
		$this->_hasLayout = true;
		$r = '<?php'.PHP_EOL."\$_layout->template = {$template};".PHP_EOL;

		$params = $this->parseParams($body);
		foreach ($params as $paramName => $paramValue)
		{
			$r .= "\$_layout->tags['{$paramName}'] = {$paramValue};".PHP_EOL;
		}

		$r .= '?>';
		return $r;
	}

	/**
	 * Parses a 'region' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseRegionTag($body)
	{
		$this->_hasLayout = true;
		$this->parseVariables($body, true);
		return "<?php \$_layout->regions[] = \$this->beginWidget('Blocks\\RegionTemplateWidget', array('name' => {$body})); ?>";
	}

	/**
	 * Parses an 'endregion' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseEndregionTag($body)
	{
		return '<?php $this->endWidget(); ?>';
	}

	/**
	 * Parses an 'include' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseIncludeTag($body)
	{
		if (!preg_match('/^('.self::stringPattern.'|'.self::tagPattern.self::subtagPattern.'?)(\s+.*)?$/x', $body, $match))
			throw new Exception('Invalid include tag');

		$template = $match[1];
		$body = isset($match[7]) ? trim($match[7]) : '';
		$this->parseVariable($template, $offset, true);
		$r = "<?php \$this->loadTemplate({$template}";
		$params = $this->parseParams($body);
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
	}

	/**
	 * Parses a 'foreach' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseForeachTag($body)
	{
		if (preg_match('/^(.+)\s+as\s+(?:([A-Za-z]\w*)\s*=>\s*)?([A-Za-z]\w*)$/m', $body, $match))
		{
			$this->parseVariable($match[1]);
			$index = '$'.(!empty($match[2]) ? $match[2] : 'index');
			$subvar = '$'.$match[3];

			return "<?php foreach ({$match[1]}->__toArray() as {$index} => {$subvar}):" . PHP_EOL .
				"{$index} = TemplateHelper::getTag({$index});" . PHP_EOL .
				"{$subvar} = TemplateHelper::getTag({$subvar}); ?>";
		}
		return '';
	}

	/**
	 * Parses an 'endforeach' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseEndforeachTag($body)
	{
		return '<?php endforeach ?>';
	}

	/**
	 * Parses an 'if' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseIfTag($body)
	{
		$this->parseVariables($body, true);
		return "<?php if ({$body}): ?>";
	}

	/**
	 * Parses an 'elseif' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseElseifTag($body)
	{
		$this->parseVariables($body, true);
		return "<?php elseif ({$body}): ?>";
	}

	/**
	 * Parses an 'else' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseElseTag($body)
	{
		return '<?php else: ?>';
	}

	/**
	 * Parses an 'endif' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseEndifTag($body)
	{
		return '<?php endif ?>';
	}

	/**
	 * Parses a 'set' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseSetTag($body)
	{
		if (preg_match('/^([A-Za-z]\w*)\s*=\s*(.*)$/m', $body, $match))
		{
			$this->parseVariables($match[2]);
			return "<?php \${$match[1]} = TemplateHelper::getTag({$match[2]}); ?>";
		}
		return '';
	}

	/**
	 * Parses a 'redirect' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseRedirectTag($body)
	{
		$this->parseVariables($body, true);
		return "<?php b()->request->redirect(UrlHelper::generateUrl({$body})); ?>";
	}

	/**
	 * Parses an 'echo' tag
	 * @param string $body
	 * @return string
	 * @access protected
	 */
	protected function parseEchoTag($body)
	{
		$this->parseVariables($body, true);
		return "<?php echo {$body} ?>";
	}

	/**
	 * Parse variables
	 * @param string $template The template to parse for variables
	 * @param bool $toString Whether to cast the variables as strings
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
	 * @access protected
	 */
	protected function parseParams($template)
	{
		$params = array();
		$template = trim($template);

		while ($template) {
			$nextEq = strpos($template, '=');

			if (!$nextEq)
				throw new Exception('Invalid parameter');

			$paramName = rtrim(substr($template, 0, $nextEq));

			if (!preg_match('/^'.self::tagPattern.'$/', $paramName))
				throw new Exception('Invalid parameter');

			$remainingTemplate = ltrim(substr($template, $nextEq+1));

			if (!$remainingTemplate)
				throw new Exception('No parameter value set');

			$recurringSubtagPattern = substr(self::subtagPattern, 0, -1).'(?P>subtag)?)';
			if (!preg_match('/^('.self::stringPattern.'|\d*\.?\d+|'.self::tagPattern.$recurringSubtagPattern.'?)(\s+|$)/x', $remainingTemplate, $match))
				throw new Exception('Invalid parameter value');

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
	 * @param bool $toString Whether to cast the variable as a string
	 * @return bool Whether a variable was found and parsed
	 * @access protected
	 */
	protected function parseVariable(&$template, &$offset = 0, $toString = false)
	{
		if (preg_match('/'.self::tagPattern.'/', $template, $tagMatch, PREG_OFFSET_CAPTURE, $offset))
		{
			$tag = $tagMatch[0][0];
			$parsedTag = ($toString ? '(string)' : '').'$'.$tag;
			$tagLength = strlen($tagMatch[0][0]);
			$tagOffset = $tagMatch[0][1];

			// search for immediately following subtags
			$substr = substr($template, $tagOffset + $tagLength);

			while (preg_match('/^'.self::subtagPattern.'/x', $substr, $subtagMatch))
			{
				$parsedTag .= '->'.$subtagMatch['func'].'(';

				if (isset($subtagMatch['params']))
				{
					$this->parseVariables($subtagMatch['params'], true);
					$parsedTag .= $subtagMatch['params'];
				}

				$parsedTag .= ')';

				// chop the subtag match from the substring
				$subtagLength = strlen($subtagMatch[0]);
				$substr = substr($substr, $subtagLength);

				// update the total tag length
				$tagLength += $subtagLength;
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
