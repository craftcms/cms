<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\errors\HttpException;
use craft\app\helpers\HeaderHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;

/**
 * Controller is a base class that all controllers in Craft extend.
 *
 * It extends Yii’s [[\yii\web\Controller]], overwriting specific methods as required.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Controller extends \yii\web\Controller
{
	// Properties
	// =========================================================================

	/**
	 * @var boolean|string[] Whether this controller’s actions can be accessed anonymously
	 *
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action IDs, then you must be logged in for any actions except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of actions allow anonymous access, but you only want require
	 * login on a few, you can set this to true and call [[requireLogin()]] in the individual methods.
	 */
	protected $allowAnonymous = false;

	// Public Methods
	// =========================================================================

	/**
	 * Renders a template, and either outputs or returns it.
	 *
	 * @param mixed $template      The name of the template to load in a format supported by
	 *                             [[\craft\app\services\Templates::findTemplate()]], or a [[StringTemplate]] object.
	 * @param array $variables     The variables that should be available to the template.
	 * @param bool  $return        Whether to return the results, rather than output them. (Default is `false`.)
	 * @param bool  $processOutput Whether the output should be processed by [[processOutput()]].
	 *
	 * @throws HttpException
	 * @return mixed The rendered template if $return is set to `true`.
	 */
	public function renderTemplate($template, $variables = [], $return = false, $processOutput = false)
	{
		if (($output = Craft::$app->templates->render($template, $variables)) !== false)
		{
			if ($processOutput)
			{
				$output = $this->processOutput($output);
			}

			if ($return)
			{
				return $output;
			}
			else
			{
				// Set the MIME type for the request based on the matched template's file extension (unless the
				// Content-Type header was already set, perhaps by the template via the {% header %} tag)
				if (!HeaderHelper::isHeaderSet('Content-Type'))
				{
					// Safe to assume that findTemplate() will return an actual template path here, and not `false`.
					// If the template didn't exist, a TemplateLoaderException would have been thrown when calling
					// Craft::$app->templates->render().
					$templateFile = Craft::$app->templates->findTemplate($template);
					$extension = IOHelper::getExtension($templateFile, 'html');

					if ($extension == 'twig')
					{
						$extension = 'html';
					}

					HeaderHelper::setContentTypeByExtension($extension);
				}

				// Set the charset header
				HeaderHelper::setHeader(['charset' => 'utf-8']);

				// Are we serving HTML or XHTML?
				if (in_array(HeaderHelper::getMimeType(), ['text/html', 'application/xhtml+xml']))
				{
					// Are there any head/foot nodes left in the queue?
					$headHtml = Craft::$app->templates->getHeadHtml();
					$footHtml = Craft::$app->templates->getFootHtml();

					if ($headHtml)
					{
						if (($endHeadPos = mb_stripos($output, '</head>')) !== false)
						{
							$output = mb_substr($output, 0, $endHeadPos).$headHtml.mb_substr($output, $endHeadPos);
						}
						else
						{
							$output .= $headHtml;
						}
					}

					if ($footHtml)
					{
						if (($endBodyPos = mb_stripos($output, '</body>')) !== false)
						{
							$output = mb_substr($output, 0, $endBodyPos).$footHtml.mb_substr($output, $endBodyPos);
						}
						else
						{
							$output .= $footHtml;
						}
					}
				}

				// Output it into a buffer, in case the Tasks service wants to close the connection prematurely
				ob_start();
				echo $output;

				// End the request
				Craft::$app->end();
			}
		}
		else
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Redirects the user to the login template if they're not logged in.
	 *
	 * @return null
	 */
	public function requireLogin()
	{
		$user = Craft::$app->getUser();

		if ($user->getIsGuest())
		{
			$user->loginRequired();
		}
	}

	/**
	 * Throws a 403 error if the current user is not an admin.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireAdmin()
	{
		// First make sure someone's actually logged in
		$this->requireLogin();

		// Make sure they're an admin
		if (!Craft::$app->getUser()->getIsAdmin())
		{
			throw new HttpException(403, Craft::t('app', 'This action may only be performed by admins.'));
		}
	}

	/**
	 * Checks whether the current user has a given permission, and ends the request with a 403 error if they don’t.
	 *
	 * @param string $permissionName The name of the permission.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requirePermission($permissionName)
	{
		if (!Craft::$app->getUser()->checkPermission($permissionName))
		{
			throw new HttpException(403);
		}
	}

	/**
	 * Checks whether the current user can perform a given action, and ends the request with a 403 error if they don’t.
	 *
	 * @param string $action The name of the action to check.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireAuthorization($action)
	{
		if (!Craft::$app->getSession()->checkAuthorization($action))
		{
			throw new HttpException(403);
		}
	}

	/**
	 * Throws a 400 error if this isn’t a POST request
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requirePostRequest()
	{
		if (!Craft::$app->getRequest()->getIsPost())
		{
			throw new HttpException(400);
		}
	}

	/**
	 * Throws a 400 error if this isn’t an Ajax request.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireAjaxRequest()
	{
		if (!Craft::$app->getRequest()->getIsAjax())
		{
			throw new HttpException(400);
		}
	}

	/**
	 * Throws a 400 error if the current request doesn’t have a valid token.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireToken()
	{
		if (!Craft::$app->getRequest()->getQueryParam(Craft::$app->config->get('tokenParam')))
		{
			throw new HttpException(400);
		}
	}

	/**
	 * Redirects the browser to a given URL.
	 *
	 * @param string $url The URL to redirect the browser to.
	 * @param bool   $terminate Whether the request should be terminated.
	 * @param int    $statusCode The status code to accompany the redirect. (Default is 302.)
	 *
	 * @return null
	 */
	public function redirect($url, $terminate = true, $statusCode = 302)
	{
		if (is_string($url))
		{
			$url = UrlHelper::getUrl($url);
		}

		if ($url !== null)
		{
			parent::redirect($url, $terminate, $statusCode);
		}
	}

	/**
	 * Redirects to the URI specified in the POST.
	 *
	 * @param mixed  $object  Object containing properties that should be parsed for in the URL.
	 * @param string $default The default URL to redirect them to, if no 'redirect' parameter exists. If this is left
	 *                        null, then the current request’s path will be used.
	 *
	 * @return null
	 */
	public function redirectToPostedUrl($object = null, $default = null)
	{
		$url = Craft::$app->getRequest()->getBodyParam('redirect');

		if ($url === null)
		{
			if ($default !== null)
			{
				$url = $default;
			}
			else
			{
				$url = Craft::$app->getRequest()->getPath();
			}
		}

		if ($object)
		{
			$url = Craft::$app->templates->renderObjectTemplate($url, $object);
		}

		$this->redirect($url);
	}

	/**
	 * Responds to the request with JSON.
	 *
	 * @param array|null $var The array that should be JSON-encoded and returned to the browser.
	 *
	 * @return null
	 */
	public function returnJson($var = [])
	{
		JsonHelper::sendJsonHeaders();

		// Output it into a buffer, in case the Tasks service wants to close the connection prematurely
		ob_start();
		echo JsonHelper::encode($var);

		Craft::$app->end();
	}

	/**
	 * Responds to the request with a JSON error message.
	 *
	 * @param string $error The error message.
	 *
	 * @return null
	 */
	public function returnErrorJson($error)
	{
		$this->returnJson(['error' => $error]);
	}

	/**
	 * Checks if a controller has overridden allowAnonymous either as an array with actions to allow anonymous access
	 * to or as a bool that applies to all actions.
	 *
	 * @param \yii\base\Action $action The action to be executed.
	 *
	 * @return bool Whether the action should continue to run.
	 */
	public function beforeAction($action)
	{
		if (
			(is_array($this->allowAnonymous) && (!preg_grep("/{$action->id}/i", $this->allowAnonymous))) ||
			$this->allowAnonymous === false
		)
		{
			$this->requireLogin();
		}

		return true;
	}
}
