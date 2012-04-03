<?php
namespace Blocks;

/**
 * ErrorHandler handles uncaught PHP errors and exceptions.
 *
 * It displays these errors using appropriate views based on the
 * nature of the error and the mode the application runs at.
 * It also chooses the most preferred language for displaying the error.
 *
 * ErrorHandler uses two sets of views:
 * <ul>
 * <li>development templates, named as <code>exception.php</code>;
 * <li>production templates, named as <code>error&lt;StatusCode&gt;.php</code>;
 * </ul>
 * where &lt;StatusCode&gt; stands for the HTTP error code (e.g. error500.php).
 * Localized templates are named similarly but located under a subdirectory
 * whose name is the language code (e.g. zh_cn/error500.php).
 *
 * Development templates are displayed when the application is in dev mode
 * (i.e. b()->config->devMode = true). Detailed error information with source code
 * are displayed in these templates. Production templates are meant to be shown
 * to end-users and are used when the application is in production mode.
 * For security reasons, they only display the error message without any
 * sensitive information.
 *
 * ErrorHandler looks for the templates from the following locations in order:
 * <ol>
 * <li><code>blocks/templates/{siteHandle}/errors</code>: when a theme is active.</li>
 * <li><code>blocks/app/templates/errors</code></li>
 * <li><code>blocks/app/framework/views</code></li>
 * </ol>
 * If the template is not found in a directory, it will be looked for in the next directory.
 *
 * The property {@link maxSourceLines} can be changed to specify the number
 * of source code lines to be displayed in development views.
 *
 * ErrorHandler is a core application component that can be accessed via
 * {@link CApplication::getErrorHandler()}.
 *
 * @property array $error The error details. Null if there is no error.
 *
 */
class ErrorHandler extends \CErrorHandler
{
	private $_error;

	/**
	 * Handles a thrown exception.  Will also log extra information if the exception happens to by a MySql deadlock.
	 * @access protected
	 * @param Exception $exception the exception captured
	 */
	protected function handleException($exception)
	{
		// extra logic to log any mysql deadlocks.
		if ($exception instanceof \CDbException && strpos($exception->getMessage(), 'Deadlock') !== false)
		{
			$data = b()->db->createCommand('SHOW ENGINE INNODB STATUS')->query();
			$info = $data->read();
			$info = serialize($info);
			Blocks::log('Deadlock error, innodb status: '.$info, \CLogger::LEVEL_ERROR, 'system.db.CDbCommand');
		}

		$app = b();
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

			// If this is a template renderer exception, we don't want to show any stack track information.
			if ($exception instanceof TemplateRendererException)
			{
				$trace = array();
			}
			else
			{
				// Build the full stack trace.
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

			// If this is an HttpException or we're not in dev mode, render the error template.
			if ($exception instanceof \CHttpException || !b()->config->devMode)
				$this->render('errors/error', $data);
			else
			{
				// If this is an ajax request, we want to prep the exception a bit before we return it.
				if($this->isAjaxRequest())
					$app->displayAjaxException($data);
				else
					// If we've made it this far, just render the exception template.
					$this->render('errors/exception', $data);
			}
		}
		else
			$app->displayException($exception);
	}

	/**
	 * Handles a PHP error event.
	 * @param \CErrorEvent $event the PHP error event
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

		$app = b();
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
				$app->displayAjaxError($event->code, $event->message, $event->file, $event->line);
			else if(b()->config->devMode == true)
				$this->render('errors/exception', $data);
			else
				$this->render('errors/error', $data);
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
			b()->runController($this->errorAction);
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
		$templateFile = b()->findLocalizedFile($templatePath.$templateName.b()->viewRenderer->fileExtension, $srcLanguage);
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
			b()->theme === null ? null : b()->theme->systemViewPath,
			b() instanceof \CWebApplication ? b()->systemViewPath : null,
			b()->path->frameworkPath.'views/',
		);

		try
		{
			$connection = b()->db;
			if ($connection && b()->db->schema->getTable('{{site}}' !== null))
				$viewPaths[] = b()->path->siteTemplatesPath;
		}
		catch(\CDbException $e)
		{
			// swallow the exception.
		}

		foreach ($viewPaths as $i => $viewPath)
		{
			if ($viewPath !== null)
			{
				// if it's an exception on the front-end, we don't show the exception template, on the error template.
				if ($view == 'errors/exception' && b()->request->mode == RequestMode::Site)
					$view = 'errors/error';

				$viewFile = $this->getViewFileInternal($viewPath, $view, $code, $i === 2 ? 'en_us' : null);
				if (is_file($viewFile))
					return $viewFile;
			}
		}

		return null;
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
			$version = '<a href="http://blockscms.com/">Blocks '.Blocks::getEdition(false).'.</a> v'.Blocks::getVersion(false).' build '.Blocks::getBuild(false);
			if(isset($_SERVER['SERVER_SOFTWARE']))
				$version = $_SERVER['SERVER_SOFTWARE'].' '.$version;
		}
		else
			$version = '';

		return $version;
	}
}
