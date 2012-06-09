<?php
namespace Blocks;

/**
 *
 */
class EmailTemplateProcessor extends BaseTemplateProcessor
{
	/**
	 * @param object $context
	 * @param        $email
	 * @param array  $variables
	 * @return mixed|void
	 */
	public function process($context, $email, $variables)
	{
		$subject = null;
		$html = null;
		$text = null;

		if ($email->plugin)
			$this->_plugin = $email->plugin->class;

		if (($subjectContent = $this->run($context, $email->subject, $email->key, $email->key.'.subject', $variables)) !== false)
		{
			$subject = $subjectContent;
		}

		if (StringHelper::isNotNullOrEmpty($email->html))
		{
			if (($htmlContent = $this->run($context, $email->html, $email->key, $email->key.'.html', $variables)) !== false)
			{
				$html = $htmlContent;
			}
		}

		if (StringHelper::isNotNullOrEmpty($email->text))
		{
			if (($textContent = $this->run($context, $email->text, $email->key, $email->key.'.text', $variables)) !== false)
			{
				$text = $textContent;
			}
		}

		return array('subject' => $subject, 'html' => $html, 'text' => $text);
	}

	/**
	 * Renders a template
	 *
	 * @param object $context The controller or widget who is rendering the template
	 * @param        $content
	 * @param array  $key
	 * @param bool   $fileName
	 * @param array  $variables The variables to be passed to the template
	 *
	 * @internal param $fileSystemPath
	 * @internal param bool $return Whether the rendering result should be returned
	 * @return mixed
	 */
	public function run($context, $content, $key, $fileName, $variables)
	{
		if ($this->_plugin)
		{
			$subPath = 'plugins/'.$this->_plugin.'/';
		}
		else
			$subPath = 'app/';

 		$path = b()->path->getParsedEmailTemplatesPath().$subPath.$fileName.'.html';

		$sourceFile = b()->file->set($path);
		$dir = dirname($sourceFile->getRealPath());

		if (!is_dir($dir))
			mkdir($dir, self::$_filePermission, true);

		if (!$sourceFile->getExists())
			$sourceFile->create();

		$sourceFile->setContents(null, $content);

		$this->setPaths($path, false);

		if ($this->isTemplateParsingNeeded())
			$this->parseTemplate();

		return $context->renderInternal($this->_parsedPath, $variables, true);
	}

	/**
	 * Returns the template path, relative to the template root directory
	 * @access protected
	 * @return string
	 */
	protected function getRelativePath()
	{
		return '';
	}

	/**
	 * Returns the full path to the duplicate template in the parsed_templates directory
	 * @access protected
	 * @return string
	 */
	protected function getDuplicatePath()
	{
		return $this->_sourcePath.$this->sourceExtension;
	}
}
