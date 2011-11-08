<?php

class BlocksViewRenderer extends CViewRenderer
{
	private $_input;
	private $_output;
	private $_sourceFile;

	public $filePermission = 0755;

	public function getAllowedFileExtensions()
	{
		return Blocks::app()->config->getAllowedTemplateFileExtensions();
	}

	protected function generateViewFile($sourceFile, $viewFile)
	{
		$this->_sourceFile = $sourceFile;
		$this->_input = file_get_contents($sourceFile);
		$this->_output = "<?php /* source file: {$sourceFile} */ ?>".PHP_EOL;

		$this->_output .= $this->_input;
		// when we're ready to actually translate the template, uncomment.
		//$this->parse(0, strlen($this->_input));
		file_put_contents($viewFile, $this->_output);
	}

	public function renderFile($context, $sourceFile, $data, $return)
	{
		if (!is_file($sourceFile) || ($file = realpath($sourceFile)) === false)
			throw new BlocksException(Blocks::t('blocks', 'View file "{file}" does not exist.', array('{file}' => $sourceFile)));

		$viewFile = $this->getViewFile($sourceFile);
		if(@filemtime($sourceFile) > @filemtime($viewFile))
		{
			$this->generateViewFile($sourceFile, $viewFile);
			@chmod($viewFile, $this->filePermission);
		}

		return $context->renderInternal($viewFile, $data, $return);
	}

	protected function getViewFile($file)
	{
		$cacheTemplatePath = Blocks::app()->config->getBlocksTemplateCachePath();

		$relativePath = substr($file, strlen(Blocks::app()->config->getBlocksTemplatePath()));
		$relativePath = substr($relativePath, 0, strpos($relativePath, '.'));
		$cacheTemplatePath = $cacheTemplatePath.$relativePath.'.php';

		if(!is_file($cacheTemplatePath))
			@mkdir(dirname($cacheTemplatePath), $this->filePermission, true);

		return $cacheTemplatePath;
	}

	private function parse($beginBlock, $endBlock)
	{
		$offset = $beginBlock;
		while (($pos = strpos($this->_input, "%", $offset)) !== false && $pos < $endBlock)
		{
			// replace @@ -> @
			if ($this->isNextToken($pos, $endBlock, "@"))
			{
				$this->_output .= substr($this->_input, $offset, $pos - $offset + 1);
				$offset = $pos + 2;
				continue;
			}

			// replace multi-token statements @(...)
			if ($this->isNextToken($pos, $endBlock, "("))
			{
				$end = $this->findClosingBracket($pos + 1, $endBlock, "(", ")");
				$this->_output .= substr($this->_input, $offset, $pos - $offset);
				$this->generatePHPOutput($pos, $end);
				$offset = $end + 1;
				continue;
			}

			// replace multi-line statements @{...}
			if ($this->isNextToken($pos, $endBlock, "{"))
			{
				$end = $this->findClosingBracket($pos + 1, $endBlock, "{", "}");
				$this->_output .= substr($this->_input, $offset, $pos - $offset);
				$this->_output .= "<?php " . substr($this->_input, $pos + 2, $end - $pos - 2) . " ?>";
				$offset = $end + 1;
				continue;
			}

			// replace HTML-encoded statements @:...
			if ($this->isNextToken($pos, $endBlock, ":"))
			{
				$statement = $this->detectStatement($pos + 2, $endBlock);
				$end = $this->findEndStatement($pos + 1 + strlen($statement), $endBlock);
				$this->_output .= substr($this->_input, $offset, $pos - $offset);
				$this->generatePHPOutput($pos + 1, $end, true);
				$offset = $end + 1;
				continue;
			}

			$statement = $this->detectStatement($pos + 1, $endBlock);
			if ($statement == "foreach" || $statement == "for" || $statement == "while")
			{
				$offset = $this->processLoopStatement($pos, $offset, $endBlock, $statement);
			}
			elseif ($statement == "if")
			{
				$offset = $this->processIfStatement($pos, $offset, $endBlock, $statement);
			}
			else
			{
				$end = $this->findEndStatement($pos + strlen($statement), $endBlock);
				$this->_output .= substr($this->_input, $offset, $pos - $offset);
				$this->generatePHPOutput($pos, $end);
				$offset = $end + 1;
			}
		}

		$this->_output .= substr($this->_input, $offset, $endBlock - $offset);
	}

	private function generatePHPOutput($currentPosition, $endPosition, $htmlEncode = false)
	{
		$this->_output .= "<?php echo "
				. ($htmlEncode ? "CHtml::encode(" : "")
				. substr($this->_input, $currentPosition + 1, $endPosition - $currentPosition)
				. ($htmlEncode ? ")" : "")
				. "; ?>";
	}

	private function processLoopStatement($currentPosition, $offset, $endBlock, $statement)
	{
		if (($bracketPosition = $this->findOpenBracketAtLine($currentPosition + 1, $endBlock)) === false)
		{
			throw new BlocksViewRendererException("Cannot find open bracket for '{$statement}' statement.", $this->_sourceFile, $this->getLineNumber($currentPosition));
		}

		$this->_output .= substr($this->_input, $offset, $currentPosition - $offset);
		$this->_output .= "<?php " . substr($this->_input, $currentPosition + 1, $bracketPosition - $currentPosition) . " ?>";
		$offset = $bracketPosition + 1;

		$end = $this->findClosingBracket($bracketPosition, $endBlock, "{", "}");
		$this->parse($offset, $end);
		$this->_output .= "<?php } ?>";

		return $end + 1;
	}

	private function processIfStatement($currentPosition, $offset, $endBlock, $statement)
	{
		$bracketPosition = $this->findOpenBracketAtLine($currentPosition + 1, $endBlock);
		if ($bracketPosition === false)
			throw new BlocksViewRendererException("Cannot find open bracket for '{$statement}' statement.", $this->_sourceFile, $this->getLineNumber($currentPosition));

		$this->_output .= substr($this->_input, $offset, $currentPosition - $offset);
		$start = $currentPosition + 1;
		while (true)
		{
			$this->_output .= "<?php " . substr($this->_input, $start, $bracketPosition - $start + 1) . " ?>";
			$offset = $bracketPosition + 1;

			$end = $this->findClosingBracket($bracketPosition, $endBlock, "{", "}");
			$this->parse($offset, $end);
			$offset = $end + 1;

			$bracketPosition = $this->findOpenBracketAtLine($offset, $endBlock);
			if ($bracketPosition === false)
			{
				$this->_output .= "<?php } ?>";
				break;
			}

			$start = $end;
		}

		return $offset;
	}

	private function findOpenBracketAtLine($currentPosition, $endBlock)
	{
		$openDoubleQuotes = false;
		$openSingleQuotes = false;

		for ($p = $currentPosition; $p < $endBlock; ++$p)
		{
			if ($this->_input[$p] == PHP_EOL)
				return false;

			$quotesNotOpened = !$openDoubleQuotes && !$openSingleQuotes;
			if ($this->_input[$p] == '"')
			{
				$openDoubleQuotes = $this->getQuotesState($openDoubleQuotes, $quotesNotOpened, $p);
			}
			elseif ($this->_input[$p] == "'")
			{
				$openSingleQuotes = $this->getQuotesState($openSingleQuotes, $quotesNotOpened, $p);
			}
			elseif ($this->_input[$p] == "{" && $quotesNotOpened)
			{
				return $p;
			}
		}

		return false;
	}

	// checks to see if the next token is the supplied token
	private function isNextToken($currentPosition, $endBlock, $token)
	{
		// make sure the next token isn't the end of the text
		if ($currentPosition + strlen($token) < $endBlock)
			if (substr($this->_input, $currentPosition + 1, strlen($token)) == $token)
				return true;

		return false;
	}

	private function isEscaped($currentPosition)
	{
		$cntBackSlashes = 0;
		for ($p = $currentPosition - 1; $p >= 0; --$p)
		{
			if ($this->_input[$p] != "\\")
				break;

			++$cntBackSlashes;
		}

		return $cntBackSlashes % 2 == 1;
	}

	private function getQuotesState($testedQuotes, $quotesNotOpened, $currentPosition)
	{
		if ($quotesNotOpened)
			return true;

		return $testedQuotes && !$this->isEscaped($currentPosition) ? false: $testedQuotes;
	}

	private function findClosingBracket($openBracketPosition, $endBlock, $openBracket, $closeBracket)
	{
		$opened = 0;
		$openDoubleQuotes = false;
		$openSingleQuotes = false;

		for ($p = $openBracketPosition; $p < $endBlock; ++$p)
		{
			$quotesNotOpened = !$openDoubleQuotes && !$openSingleQuotes;

			if ($this->_input[$p] == '"')
			{
				$openDoubleQuotes = $this->getQuotesState($openDoubleQuotes, $quotesNotOpened, $p);
			}
			elseif ($this->_input[$p] == "'")
			{
				$openSingleQuotes = $this->getQuotesState($openSingleQuotes, $quotesNotOpened, $p);
			}
			elseif ($this->_input[$p] == $openBracket && $quotesNotOpened)
			{
				$opened++;
			}
			elseif ($this->_input[$p] == $closeBracket && $quotesNotOpened)
			{
				if (--$opened == 0)
					return $p;
			}
		}

		throw new BlocksViewRendererException("Cannot find closing bracket.", $this->_sourceFile, $this->getLineNumber($openBracketPosition));
	}

	private function findEndStatement($endPosition, $endBlock)
	{
		if ($this->isNextToken($endPosition, $endBlock, "("))
		{
			$endPosition = $this->findClosingBracket($endPosition + 1, $endBlock, "(", ")");
			$endPosition = $this->findEndStatement($endPosition, $endBlock);
		}
		elseif ($this->isNextToken($endPosition, $endBlock, "["))
		{
			$endPosition = $this->findClosingBracket($endPosition + 1, $endBlock, "[", "]");
			$endPosition = $this->findEndStatement($endPosition, $endBlock);
		}
		elseif ($this->isNextToken($endPosition, $endBlock, "->"))
		{
			$endPosition += 2;
			$statement = $this->detectStatement($endPosition + 1, $endBlock);
			$endPosition = $this->findEndStatement($endPosition + strlen($statement), $endBlock);
		}
		elseif ($this->isNextToken($endPosition, $endBlock, "::"))
		{
			$endPosition += 2;
			$statement = $this->detectStatement($endPosition + 1, $endBlock);
			$endPosition = $this->findEndStatement($endPosition + strlen($statement), $endBlock);
		}

		return $endPosition;
	}

	private function detectStatement($currentPosition, $endBlock)
	{
		$invalidCharPosition = $endBlock;
		for ($p = $currentPosition; $p < $invalidCharPosition; ++$p) {
			if ($this->_input[$p] == "$" && $p == $currentPosition) {
				continue;
			}

			if (preg_match('/[a-zA-Z0-9_]/', $this->_input[$p])) {
				continue;
			}

			$invalidCharPosition = $p;
			break;
		}

		if ($currentPosition == $invalidCharPosition)
			throw new BlocksViewRendererException("Cannot detect statement.", $this->_sourceFile, $this->getLineNumber($currentPosition));

		return substr($this->_input, $currentPosition, $invalidCharPosition - $currentPosition);
	}

	private function getLineNumber($currentPosition)
	{
		return count(explode(PHP_EOL, substr($this->_input, 0, $currentPosition)));
	}
}
