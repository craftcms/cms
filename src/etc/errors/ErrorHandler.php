<?php
namespace Craft;

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
 * (i.e. craft()->config->get('devMode') = true). Detailed error information with source code
 * are displayed in these templates. Production templates are meant to be shown
 * to end-users and are used when the application is in production mode.
 * For security reasons, they only display the error message without any
 * sensitive information.
 *
 * ErrorHandler looks for the templates from the following locations in order:
 * <ol>
 * <li><code>craft/templates/{siteHandle}/errors</code>: when a theme is active.</li>
 * <li><code>craft/app/templates/errors</code></li>
 * <li><code>craft/app/framework/views</code></li>
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
 */
class ErrorHandler extends \CErrorHandler
{
	private $_error;
	private $_devMode = false;

	/**
	 *
	 */
	public function init()
	{
		parent::init();

		$admin = false;

		if (!craft()->isConsole() && Craft::isInstalled())
		{
			// Set whether the currently logged in user is an admin.
			if (isset(craft()->userSession))
			{
				if (($currentUser = craft()->userSession->getUser()) !== null)
				{
					$admin = $currentUser->admin == 1 ? true : false;
				}
			}
		}

		$this->_devMode = craft()->config->get('devMode') || $admin;
	}

	/**
	 * Handles a thrown exception.  Will also log extra information if the exception happens to by a MySql deadlock.
	 *
	 * @access protected
	 * @param Exception $exception the exception captured
	 */
	protected function handleException($exception)
	{
		// extra logic to log any mysql deadlocks.
		if ($exception instanceof \CDbException && strpos($exception->getMessage(), 'Deadlock') !== false)
		{
			$data = craft()->db->createCommand('SHOW ENGINE INNODB STATUS')->query();
			$info = $data->read();
			$info = serialize($info);

			Craft::log('Deadlock error, innodb status: '.$info, LogLevel::Error, 'system.db.CDbCommand');
		}

		$app = craft();
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

			// Check for Twig exceptions that wrapped another exception
			if ($exception instanceof \Twig_Error_Runtime)
			{
				$previousException = $exception->getPrevious();

				if ($previousException)
				{
					$exception = $previousException;
				}
			}

			// If this is a Twig exception, show the template instead
			if ($exception instanceof \Twig_Error)
			{
				// This is the template file for the exception.
				try
				{
					$templateFile = craft()->templates->findTemplate($exception->getTemplateFile());
				}
				catch (TemplateLoaderException $e)
				{
					$templateFile = $exception->getTemplateFile();
				}

				$this->_error = $data = array(
					'code' => 500,
					'type' => Craft::t('Template Syntax Error'),
					'errorCode' => $exception->getCode(),
					'message' => $exception->getMessage(),
					'file' => $templateFile,
					'line' => $exception->getTemplateLine(),
					'trace' => '',
				);
			}
			else
			{
				// Build the full stack trace.
				$trace = $exception->getTrace();
				$trace = $this->prepStackTrace($trace);

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
			}

			if (!headers_sent())
			{
				header("HTTP/1.0 {$data['code']} ".get_class($exception));
			}

			// If this is an HttpException or we're not in dev mode, render the error template.
			if ($exception instanceof \CHttpException || !$this->_devMode)
			{
				if ($this->isAjaxRequest())
				{
					$app->returnAjaxError($data['code'], $data['message'], $data['file'], $data['line']);
				}
				else
				{
					// If it's a database connection problem, display a special template.
					if ($exception instanceof DbConnectException)
					{
						$this->render('_special/dbconnect', $data);
					}
					else
					{
						$this->render('error', $data);
					}
				}
			}
			else
			{
				// If this is an ajax request, we want to prep the exception a bit before we return it.
				if ($this->isAjaxRequest())
				{
					$app->returnAjaxException($data);
				}
				else
				{
					// If it's a database connection problem, display a special template.
					if ($exception instanceof DbConnectException)
					{
						$this->render('_special/dbconnect', $data);
					}
					else
					{
						$this->render('exception', $data);
					}
				}
			}
		}
		else
		{
			$app->displayException($exception);
		}
	}

	/**
	 * @param       $templateFile
	 * @param array $trace
	 * @return mixed
	 */
	protected function processTemplateStackTrace($templateFile, $trace = array())
	{
		if (($lineNumber = $this->getExtendsTemplateLineNumber($templateFile)) !== false)
		{
			if (($fileName = $this->getExtendsTemplateName($templateFile)) !== false)
			{
				$temp['line'] = $lineNumber;
				$temp['file'] = $templateFile;
				array_push($trace, $temp);
				$trace = $this->processTemplateStackTrace($fileName, $trace);
			}
		}

		return $trace;
	}

	/**
	 * @param $templateFile
	 * @return array|bool
	 */
	protected function getExtendsTemplateLineNumber($templateFile)
	{
		$contents = IOHelper::getFileContents($templateFile, true);
		$matches = preg_grep("/({%\s*extends\s*('|\"))([A-Za-z0-9_\\/]*)('|\")(\s%})/uis", $contents);

		if (count($matches) > 0)
		{
			$lineNo = array_keys($matches);
			$lineNo = $lineNo[0] + 1;
			return $lineNo;
		}

		return false;
	}

	/**
	 * @param $templateFile
	 * @return bool
	 */
	protected function getExtendsTemplateName($templateFile)
	{
		$contents = IOHelper::getFileContents($templateFile);
		$n = preg_match("/({%\s*extends\s*('|\"))([A-Za-z0-9_\\/]*)('|\")(\s%})/uis", $contents, $matches);

		if ($n > 0)
		{
			if (isset($matches[3]))
			{
				return craft()->templates->findTemplate($matches[3]);
			}
		}

		return false;
	}

	/**
	 * @param $trace
	 * @return mixed
	 */
	protected function prepStackTrace($trace)
	{
		foreach ($trace as $i => $t)
		{
			if (!isset($t['file']))
			{
				$trace[$i]['file'] = 'unknown';
			}

			if (!isset($t['line']))
			{
				$trace[$i]['line'] = 0;
			}

			if (!isset($t['function']))
			{
				$trace[$i]['function'] = '';
			}

			unset($trace[$i]['object']);
		}

		return $trace;
	}

	/**
	 * Handles a PHP error event.
	 *
	 * @param \CErrorEvent $event the PHP error event
	 */
	protected function handleError($event)
	{
		$trace = debug_backtrace();

		// skip the first 3 stacks as they do not tell the error position
		if (count($trace) > 3)
		{
			$trace = array_slice($trace, 3);
		}

		$traceString = '';
		foreach ($trace as $i => $t)
		{
			if (!isset($t['file']))
			{
				$trace[$i]['file'] = 'unknown';
			}

			if (!isset($t['line']))
			{
				$trace[$i]['line'] = 0;
			}

			if (!isset($t['function']))
			{
				$trace[$i]['function'] = 'unknown';
			}

			$traceString .= "#$i {$trace[$i]['file']}({$trace[$i]['line']}): ";

			if (isset($t['object']) && is_object($t['object']))
			{
				$traceString .= get_class($t['object']).'->';
			}

			$traceString .= "{$trace[$i]['function']}()\n";

			unset($trace[$i]['object']);
		}

		$app = craft();
		if ($app instanceof \CWebApplication)
		{
			switch ($event->code)
			{
				case E_WARNING:
				{
					$type = 'PHP warning';
					break;
				}
				case E_NOTICE:
				{
					$type = 'PHP notice';
					break;
				}
				case E_USER_ERROR:
				{
					$type = 'User error';
					break;
				}
				case E_USER_WARNING:
				{
					$type = 'User warning';
					break;
				}
				case E_USER_NOTICE:
				{
					$type = 'User notice';
					break;
				}
				case E_RECOVERABLE_ERROR:
				{
					$type = 'Recoverable error';
					break;
				}
				default:
				{
						$type = 'PHP error';
				}
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
			{
				header("HTTP/1.0 500 PHP Error");
			}

			if ($this->isAjaxRequest())
			{
				$app->returnAjaxError($event->code, $event->message, $event->file, $event->line);
			}
			else if($this->_devMode)
			{
				$this->render('exception', $data);
			}
			else
			{
				$this->render('error', $data);
			}
		}
		else
		{
			$app->displayError($event->code, $event->message, $event->file, $event->line);
		}
	}

	/**
	 * Renders the template.
	 *
	 * @access protected
	 * @param string $template the template name (file name without extension).
	 * See {@link getViewFile} for how a template is located given its name.
	 * @param array $data data to be passed to the template
	 */
	protected function render($template, $data)
	{
		$viewFile = $this->getViewFile($template, $data['code'], $data['type']);

		if (($this->_devMode) && $template == 'exception')
		{
			$data['version'] = $this->getVersionInfo();
			$data['time'] = time();

			include($viewFile);
		}
		else
		{
			$fileName = IOHelper::getFileName($viewFile, false);

			// If this is a site request, make sure the error template exists
			if (craft()->request->isSiteRequest())
			{
				if (!IOHelper::fileExists(craft()->path->getSiteTemplatesPath().$fileName.'.html'))
				{
					// Set PathService to use the CP templates path instead
					craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());
				}
			}

			$path = mb_substr($viewFile, mb_strlen(craft()->path->getTemplatesPath()));
			$path = mb_substr($path, 0, mb_strpos($path, '.'));

			try
			{
				$controller = craft()->getController();
				$baseControllerClass = __NAMESPACE__.'\\BaseController';

				if ($controller && ($controller instanceof $baseControllerClass))
				{
					$controller->renderTemplate($path, $data);
				}
				else if (($output = craft()->templates->render($path, $data)) !== false)
				{
					echo $output;
				}
				else
				{
					echo '<h1>'.Craft::t('There was a problem rendering the error template.').'</h1>';
				}
			}
			catch (\Exception $e)
			{
				craft()->displayException($e);
			}
		}
	}

	/**
	 * Looks for the template under the specified directory.
	 *
	 * @access protected
	 * @param  string      $templatePath the folder containing the views
	 * @param  string      $templateName template name (either 'exception' or 'error')
	 * @param  integer     $code         HTTP status code
	 * @param  string      $type         A string description of the type of error.
	 * @param  string      $srcLanguage  the language that the template is in
	 *
	 * @return string template path
	 */
	protected function getViewFileInternal($templatePath, $templateName, $code, $type, $srcLanguage = null)
	{
		if (strpos($templatePath, '/framework/') !== false)
		{
			$extension = 'php';
		}
		else
		{
			$extension = 'html';

			// Grab the numeric template from the code unless we're looking in the "_special" directory and it's not a Twig template syntax error
			if ($code && is_numeric($code) && strpos($templateName, '_special') === false && $type != 'Template Syntax Error')
			{
				// If it's a 200 HttpException, use the error template.
				if ((string)$code == '200')
				{
					$templateName = 'error';
				}
				else
				{
					$templateName = (string)$code;
				}

			}
		}

		if ($templateName == 'error')
		{
			if (!empty($code))
			{
				$templateFile = craft()->findLocalizedFile(IOHelper::getRealPath($templatePath.'/'.$templateName.'.'.$extension), $srcLanguage);

				if (IOHelper::fileExists($templateFile))
				{
					return IOHelper::getRealPath($templateFile);
				}

				return null;
			}
		}

		$templateFile = craft()->findLocalizedFile(IOHelper::getRealPath($templatePath.'/'.$templateName.'.'.$extension), $srcLanguage);

		if (IOHelper::fileExists($templateFile))
		{
			$templateFile = IOHelper::getRealPath($templateFile);
		}

		return $templateFile;
	}

	/**
	 * Determines which view file should be used.
	 *
	 * @access protected
	 * @param  string  $view view name (either 'exception' or 'error')
	 * @param  integer $code HTTP status code
	 * @param  string $type
	 * @return string view file path
	 */
	protected function getViewFile($view, $code, $type)
	{
		$viewPaths = array();

		if (($this->_devMode) && $view == 'exception')
		{
			$viewPaths[] = craft()->path->getFrameworkPath().'views/';
		}
		else
		{
			if (craft()->request->isSiteRequest())
			{
				$viewPaths[] = craft()->path->getSiteTemplatesPath();
			}

			$viewPaths[] = craft()->path->getCpTemplatesPath();
		}

		for ($counter = 0; $counter < count($viewPaths); $counter ++)
		{
			$viewFile = $this->getViewFileInternal($viewPaths[$counter], $view, $code, $type, null);

			if (IOHelper::fileExists($viewFile))
			{
				return $viewFile;
			}
		}

		return null;
	}

	/**
	 * Returns server version information.
	 * If the application is in production mode, empty string is returned.
	 *
	 * @return string server version information. Empty if in production mode.
	 */
	protected function getVersionInfo()
	{
		if ($this->_devMode)
		{
			$version = '<a href="http://buildwithcraft.com/">@@@appName@@@</a> ' .
				Craft::t('{version} build {build}', array(
					'version' => CRAFT_VERSION,
					'build'   => CRAFT_BUILD
				));

			if (isset($_SERVER['SERVER_SOFTWARE']))
			{
				$version = $_SERVER['SERVER_SOFTWARE'].' '.$version;
			}
		}
		else
		{
			$version = '';
		}

		return $version;
	}
}
