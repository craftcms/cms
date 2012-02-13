<?php
namespace Blocks;

/**
 *
 */
class ErrorHandler extends \CErrorHandler
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
		if ($app instanceof \CWebApplication)
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
				'code' => ($exception instanceof \CHttpException) ? $exception->statusCode : 500,
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
			if($exception instanceof CHttpException || !YII_DEBUG)
				$this->render('errors/error',$data);
			else
			{
				if($this->isAjaxRequest())
					$app->displayException($exception);
				else
					$this->render('errors/exception',$data);
			}
		}
		else
			$app->displayException($exception);
	}

	/**
	 * Handles the PHP error.
	 * @param CErrorEvent $event the PHP error event
	 */
	protected function handleError($event)
	{
		$trace = debug_backtrace();

		// skip the first 3 stacks as they do not tell the error position
		if (count($trace) > 3)
			$trace = array_slice($trace, 3);

		$traceString = '';
		foreach ($trace as $i => $t)
		{
			if (!isset($t['file']))
				$trace[$i]['file'] = 'unknown';

			if (!isset($t['line']))
				$trace[$i]['line'] = 0;

			if (!isset($t['function']))
				$trace[$i]['function'] = 'unknown';

			$traceString .= "#$i {$trace[$i]['file']}({$trace[$i]['line']}): ";

			if (isset($t['object']) && is_object($t['object']))
				$traceString .= get_class($t['object']).'->';

			$traceString .= "{$trace[$i]['function']}()\n";

			unset($trace[$i]['object']);
		}

		$app = Blocks::app();
		if ($app instanceof \CWebApplication)
		{
			switch ($event->code)
			{
				case E_WARNING:
					$type = 'PHP warning';
					break;
				case E_NOTICE:
					$type = 'PHP notice';
					break;
				case E_USER_ERROR:
					$type = 'User error';
					break;
				case E_USER_WARNING:
					$type = 'User warning';
					break;
				case E_USER_NOTICE:
					$type = 'User notice';
					break;
				case E_RECOVERABLE_ERROR:
					$type = 'Recoverable error';
					break;
				default:
					$type = 'PHP error';
			}

			$this->_error = $data = array(
				'code'   => 500,
				'type'   => $type,
				'message'=> $event->message,
				'file'   => $event->file,
				'line'   => $event->line,
				'trace'  => $traceString,
				'traces' => $trace,
			);

			if (!headers_sent())
				header("HTTP/1.0 500 PHP Error");

			if ($this->isAjaxRequest())
				$app->displayError($event->code, $event->message, $event->file, $event->line);
			else if(YII_DEBUG)
				$this->render('errors/exception',$data);
			else
				$this->render('errors/error',$data);
		}
		else
			$app->displayError($event->code, $event->message, $event->file, $event->line);
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
		$templateFile = Blocks::app()->findLocalizedFile($templatePath.$templateName.Blocks::app()->viewRenderer->fileExtension, $srcLanguage);
		if (is_file($templateFile))
			$templateFile = realpath($templateFile);

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
			Blocks::app() instanceof \CWebApplication ? Blocks::app()->systemViewPath : null,
			Blocks::app()->path->frameworkPath.'views/',
		);

		try
		{
			$connection = Blocks::app()->db;
			if ($connection && Blocks::app()->db->schema->getTable('{{site}}' !== null))
				$viewPaths[] = Blocks::app()->path->siteTemplatePath;
		}
		catch(Exception $e)
		{
			// swallow the exception.
		}

		foreach ($viewPaths as $i => $viewPath)
		{
			if ($viewPath !== null)
			{
				// if it's an exception on the front-end, we don't show the exception template, on the error template.
				if ($view == 'errors/exception' && Blocks::app()->request->mode == RequestMode::Site)
					$view = 'errors/error';

				$viewFile = $this->getViewFileInternal($viewPath, $view, $code, $i === 2 ? 'en_us' : null);
				if (is_file($viewFile))
					return $viewFile;
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
			$version = '<a href="http://blockscms.com/">Blocks '.Blocks::getEdition(false).'.</a> v'.Blocks::getVersion(false).'.'.Blocks::getBuild(false);
			if(isset($_SERVER['SERVER_SOFTWARE']))
				$version = $_SERVER['SERVER_SOFTWARE'].' '.$version;
		}
		else
			$version = '';

		return $version;
	}
}
