<?php

/**
 *
 */
class BlocksErrorHandler extends CErrorHandler
{
	private $_error;

	/**
	 * Handles the exception.
	 * @access protected
	 * @param Exception $exception the exception captured
	 */
	protected function handleException($exception)
	{
		$app = Blocks::app();
		if ($app instanceof CWebApplication)
		{
			if (($trace = $this->getExactTrace($exception)) === null)
			{
				$fileName = $exception->getFile();
				$errorLine = $exception->getLine();
			}
			else
			{
				$fileName = $trace['file'];
				$errorLine = $trace['line'];
			}

			$trace = $exception->getTrace();

			foreach ($trace as $i => $t)
			{
				if (!isset($t['file']))
					$trace[$i]['file'] = 'unknown';

				if (!isset($t['line']))
					$trace[$i]['line'] = 0;

				if (!isset($t['function']))
					$trace[$i]['function'] = 'unknown';

				unset($trace[$i]['object']);
			}

			$this->_error = $data = array(
				'code' => ($exception instanceof CHttpException) ? $exception->statusCode : 500,
				'type' => get_class($exception),
				'errorCode' => $exception->getCode(),
				'message' => $exception->getMessage(),
				'file' => $fileName,
				'line' => $errorLine,
				'trace' => $exception->getTraceAsString(),
				'traces' => $trace,
			);

			if (!headers_sent())
				header("HTTP/1.0 {$data['code']} ".get_class($exception));
			if ($this->isAjaxRequest())
				$app->displayException($exception);
			else if($exception instanceof CHttpException || !YII_DEBUG)
				$this->render('errors/error', $data);
			else
				$this->render('errors/exception', $data);
		}
		else
			$app->displayException($exception);
	}

	/**
	 * Renders the template.
	 * @access protected
	 * @param string $template the template name (file name without extension).
	 * See {@link getViewFile} for how a template is located given its name.
	 * @param array $data data to be passed to the template
	 */
	protected function render($template, $data)
	{
		if($template === 'errors/error' && $this->errorAction !== null)
			Blocks::app()->runController($this->errorAction);
		else
		{
			// additional information to be passed to view
			$data['version'] = $this->getVersionInfo();
			$data['time'] = time();
			$data['admin'] = $this->adminInfo;
			include($this->getViewFile($template, $data['code']));
		}
	}

	/**
	 * Looks for the template under the specified directory.
	 * @access protected
	 * @param string $templatePath the directory containing the views
	 * @param string $templateName template name (either 'errors/exception' or 'errors/error')
	 * @param integer $code HTTP status code
	 * @param string $srcLanguage the language that the template is in
	 * @return string template path
	 */
	protected function getViewFileInternal($templatePath, $templateName, $code, $srcLanguage = null)
	{
		$templateFile = null;

		if($templateName === 'errors/error')
		{
			if (($templateFile = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($templatePath.'errors/error'.$code, $srcLanguage)) == null)
				$templateFile = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($templatePath.'errors/error', $srcLanguage);
		}
		else
			$templateFile = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($templatePath.'errors/exception', $srcLanguage);

		return $templateFile;
	}

	/**
	 * Determines which view file should be used.
	 * @access protected
	 * @param string $view view name (either 'exception' or 'error')
	 * @param integer $code HTTP status code
	 * @return string view file path
	 */
	protected function getViewFile($view, $code)
	{
		$viewPaths = array(
			Blocks::app()->theme === null ? null : Blocks::app()->theme->systemViewPath,
			Blocks::app()->path->siteTemplatePath,
			Blocks::app() instanceof CWebApplication ? Blocks::app()->systemViewPath : null,
			Blocks::app()->path->frameworkPath.'views/',
		);

		foreach ($viewPaths as $i => $viewPath)
		{
			if ($viewPath !== null)
			{
				// we don't want to allow an exception template on the front end
				if ($view !== 'errors/exception' || ($view == 'errors/exception' && $viewPath !== Blocks::app()->path->siteTemplatePath))
				{
					$viewFile = $this->getViewFileInternal($viewPath, $view, $code, $i === 2 ? 'en_us' : null);
					if (is_file($viewFile))
						return $viewFile;
				}
			}
		}
	}

	/**
	 * Returns server version information.
	 * If the application is in production mode, empty string is returned.
	 * @access protected
	 * @return string server version information. Empty if in production mode.
	 */
	protected function getVersionInfo()
	{
		if(YII_DEBUG)
		{
			$version = '<a href="http://blockscms.com/">Blocks '.Blocks::getEdition().'.</a> v'.Blocks::getVersion().'.'.Blocks::getBuild();
			if(isset($_SERVER['SERVER_SOFTWARE']))
				$version = $_SERVER['SERVER_SOFTWARE'].' '.$version;
		}
		else
			$version = '';

		return $version;
	}
}
