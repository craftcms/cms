<?php
namespace Craft;

/**
 * ErrorHandler handles uncaught PHP errors and exceptions.
 *
 * It displays these errors using appropriate views based on the nature of the error and the mode the application runs
 * at. It also chooses the most preferred language for displaying the error.
 *
 * ErrorHandler uses two sets of views:
 *
 * * development templates, named as `exception.php`;
 * * production templates, named as `error<StatusCode>.php`;
 *
 * where `<StatusCode>` stands for the HTTP error code (e.g. `error500.php`). Localized templates are named similarly but
 * located under a subdirectory whose name is the language code (e.g. `zh_cn/error500.php`).
 *
 * Development templates are displayed when the application is in dev mode (i.e. craft()->config->get('devMode') = true).
 * Detailed error information with source code are displayed in these templates. Production templates are meant to be
 * shown to end-users and are used when the application is in production mode. For security reasons, they only display
 * the error message without any sensitive information.
 *
 * ErrorHandler looks for the templates from the following locations in order:
 *
 * * `craft/templates/{siteHandle}/errors`: when a theme is active.
 * * `craft/app/templates/errors`
 * * `craft/app/framework/views`
 *
 * If the template is not found in a directory, it will be looked for in the next directory. The property
 * {@link maxSourceLines} can be changed to specify the number of source code lines to be displayed in development views.
 *
 * ErrorHandler is a core application component that can be accessed via {@link \CApplication::getErrorHandler()}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.errors
 * @since     1.0
 */
class ErrorHandler extends \CErrorHandler
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_error;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the stored error, if there is one.
	 *
	 * @return array|null
	 */
	public function getError()
	{
		if (isset($this->_error))
		{
			return $this->_error;
		}
		else
		{
			return parent::getError();
		}
	}

	/**
	 * Logs an exception in the same way that {@link \CWebApplication::handleException()} does.
	 *
	 * @param \Exception $exception The exception that should be logged
	 *
	 * @return void
	 */
	public function logException(\Exception $exception)
	{
		$category = 'exception.'.get_class($exception);

		if ($exception instanceof \CHttpException)
		{
			$category .= '.'.$exception->statusCode;
		}

		$message = (string)$exception;

		if (isset($_SERVER['REQUEST_URI']))
		{
			$message .= "\nREQUEST_URI=".$_SERVER['REQUEST_URI'];
		}

		if (isset($_SERVER['HTTP_REFERER']))
		{
			$message .= "\nHTTP_REFERER=".$_SERVER['HTTP_REFERER'];
		}

		$message .= "\n---";
		Craft::log($message, \CLogger::LEVEL_ERROR, false, $category);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Handles a thrown exception.  Will also log extra information if the exception happens to by a MySql deadlock.
	 *
	 * @param \Exception $exception The exception captured.
	 *
	 * @return null
	 */
	protected function handleException($exception)
	{
		// Log MySQL deadlocks
		if ($exception instanceof \CDbException && strpos($exception->getMessage(), 'Deadlock') !== false)
		{
			$data = craft()->db->createCommand('SHOW ENGINE INNODB STATUS')->query();
			$info = $data->read();
			$info = serialize($info);

			Craft::log('Deadlock error, innodb status: '.$info, LogLevel::Error, 'system.db.CDbCommand');
		}

		// If this is a Twig Runtime exception, use the previous one instead
		if ($exception instanceof \Twig_Error_Runtime)
		{
			if ($previousException = $exception->getPrevious())
			{
				$exception = $previousException;
			}
		}

		// Special handling for Twig syntax errors
		if ($exception instanceof \Twig_Error)
		{
			$this->handleTwigError($exception);
		}
		else if ($exception instanceof DbConnectException)
		{
			$this->handleDbConnectionError($exception);
		}
		else
		{
			parent::handleException($exception);
		}
	}

	/**
	 * Handles a PHP error.
	 *
	 * @param \CErrorEvent $event the PHP error event
	 *
	 * @return null
	 */
	protected function handleError($event)
	{
		$trace = debug_backtrace();

		// Was this triggered by a Twig template directly?
		if (isset($trace[3]['object']) && $trace[3]['object'] instanceof \Twig_Template)
		{
			$exception = new \Twig_Error_Runtime($event->message);
			$this->handleTwigError($exception);
		}
		else
		{
			// Check to see if this happened while running a task
			foreach ($trace as $step)
			{
				if (isset($step['class']) && $step['class'] == __NAMESPACE__.'\\TasksService' && $step['function'] == 'runTask')
				{
					$task = craft()->tasks->getRunningTask();

					if ($task)
					{
						craft()->tasks->fail($task, $event->message.' on line '.$event->line.' of '.$event->file);
					}

					break;
				}
			}

			parent::handleError($event);
		}
	}

	/**
	 * Handles Twig syntax errors.
	 *
	 * @param \Twig_Error $exception
	 *
	 * @return null
	 */
	protected function handleTwigError(\Twig_Error $exception)
	{
		$templateFile = $exception->getTemplateFile();
		$file = craft()->templates->findTemplate($templateFile);

		if (!$file)
		{
			$file = $templateFile;
		}

		$this->_error = $data = array(
			'code'      => 500,
			'type'      => Craft::t('Template Error'),
			'errorCode' => $exception->getCode(),
			'message'   => $exception->getRawMessage(),
			'file'      => $file,
			'line'      => $exception->getTemplateLine(),
			'trace'     => '',
			'traces'    => null,
		);

		if (!headers_sent())
		{
			HeaderHelper::setHeader("HTTP/1.0 {$data['code']} ".$this->getHttpHeader($data['code'], get_class($exception)));
		}

		if ($exception instanceof \CHttpException || !YII_DEBUG)
		{
			$this->renderError();
		}
		else
		{
			if ($this->isAjaxRequest())
			{
				craft()->displayException($exception);
			}
			else
			{
				$this->render('exception',$data);
			}
		}
	}

	/**
	 * Handles DB connection errors.
	 *
	 * @param DbConnectException $exception
	 *
	 * @return null
	 */
	protected function handleDbConnectionError(DbConnectException $exception)
	{
		$this->_error = $data = array(
			'code'      => 'error',
			'type'      => get_class($exception),
			'errorCode' => null,
			'message'   => $exception->getMessage(),
			'file'      => null,
			'line'      => null,
			'trace'     => '',
			'traces'    => null,
		);

		if (!headers_sent())
		{
			HeaderHelper::setHeader('HTTP/1.0 500 '.$this->getHttpHeader(500, get_class($exception)));
		}

		$this->render('error', $data);
	}

	/**
	 * Renders the exception information. This method will display information from current {@link error} value.
	 */
	protected function renderError()
	{
		// This could be an exception because handleException can call renderError.
		$exception = $this->getException();

		// If the exception exists, and it's an instance of HttpException or devMode isn't enabled
		// set the errorAction to our TemplatesController->renderError().
		if (!YII_DEBUG || $exception instanceof HttpException)
		{
			$this->errorAction = 'templates/renderError';
		}

		parent::renderError();
	}

	/**
	 * Returns server version information. If the site is in non-dev mode, an empty string is returned.
	 *
	 * @return string The server version information. Empty if in non-dev mode.
	 */
	protected function getVersionInfo()
	{
		if (YII_DEBUG)
		{
			$version = '<a href="http://craftcms.com/">Craft CMS</a> '.CRAFT_VERSION;

			if (isset($_SERVER['SERVER_SOFTWARE']))
			{
				$version = $_SERVER['SERVER_SOFTWARE'].' / '.$version;
			}
		}
		else
		{
			$version = '';
		}

		return $version;
	}
}
