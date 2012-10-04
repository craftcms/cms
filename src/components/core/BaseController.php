<?php
namespace Blocks;

/**
 *
 */
abstract class BaseController extends \CController
{
	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in the array list.
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require login on a few, it's best to use $this->requireLogin() in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = false;

	/**
	 * Returns the folder containing view files for this controller.
	 * We're overriding this since CController's version defaults $module to blx().
	 *
	 * @return string The folder containing the view files for this controller.
	 */
	public function getViewPath()
	{
		if (($module = $this->getModule()) === null)
		{
			$module = blx();
		}

		return $module->getViewPath().'/';
	}

	/**
	 * Gets a date from post.
	 *
	 * @access protected
	 * @param string $name
	 * @return DateTime|null
	 */
	protected function getDateFromPost($name)
	{
		$timestamp = blx()->request->getPost($name);

		if ($timestamp)
		{
			return DateTime::createFromFormat(DateTime::W3C_DATE, $timestamp);
		}
	}

	/**
	 * Renders and outputs the template requested by the URL
	 * and sets the Content-Type header based on the URL extension.
	 *
	 * @param array|null $variables
	 * @throws HttpException
	 * @throws TemplateLoaderException
	 * @return void
	 */
	public function renderRequestedTemplate($variables = array())
	{
		if (($template = blx()->urlManager->processTemplateMatching()) !== false)
		{
			$variables = array_merge(blx()->urlManager->getTemplateVariables(), $variables);

			try
			{
				$output = $this->renderTemplate($template, $variables, true);
			}
			catch (TemplateLoaderException $e)
			{
				if ($e->template == $template)
				{
					throw new HttpException(404);
				}
				else
				{
					throw $e;
				}
			}

			// Set the Content-Type header
			$mimeType = blx()->request->getMimeType();
			header('Content-Type: '.$mimeType);

			// Output to the browser!
			echo $output;
		}
		else
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Renders a template, and either outputs or returns it.
	 *
	 * @param mixed $template The name of the template to load, or a StringTemplate object
	 * @param array $variables The variables that should be available to the template
	 * @param bool $return Whether to return the results, rather than output them
	 * @param bool  $processOutput
	 * @throws HttpException
	 * @return mixed
	 */
	public function renderTemplate($template, $variables = array(), $return = false, $processOutput = false)
	{
		if (($output = blx()->templates->render($template, $variables)) !== false)
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
				echo $output;
			}
		}
		else
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Redirects user to the login template if they're not logged in
	 */
	public function requireLogin()
	{
		if (blx()->user->isGuest())
		{
			blx()->user->loginRequired();
		}
	}

	/**
	 * Requires the current user to be logged in as an admin
	 */
	public function requireAdmin()
	{
		if (!blx()->account->isAdmin())
		{
			throw new HttpException(403, Blocks::t('This action may only be performed by admins.'));
		}
	}

	/**
	 * Returns a 404 if this isn't a POST request
	 * @throws HttpException
	 */
	public function requirePostRequest()
	{
		if (!blx()->config->devMode && blx()->request->getRequestType() !== 'POST')
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Returns a 404 if this isn't an Ajax request
	 * @throws HttpException
	 */
	public function requireAjaxRequest()
	{
		if (!blx()->config->devMode && !blx()->request->isAjaxRequest())
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Redirect
	 *
	 * @param      $url
	 * @param bool $terminate
	 * @param int  $statusCode
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
	 * @param array|null $variables Variables to parse in the URL
	 */
	public function redirectToPostedUrl($variables = array())
	{
		$url = blx()->request->getPost('redirect');

		if ($url === null)
		{
			$url = blx()->request->getPath();
		}

		foreach ($variables as $name => $value)
		{
			$url = str_replace('{'.$name.'}', $value, $url);
		}

		$this->redirect($url);
	}

	/**
	 * Respond with JSON
	 *
	 * @param array $r The array to JSON-encode and return
	 */
	public function returnJson($r)
	{
		JsonHelper::sendJsonHeaders();
		echo JsonHelper::encode($r);
		blx()->end();
	}

	/**
	 * Respond with a JSON error message
	 *
	 * @param string $error The error message
	 */
	public function returnErrorJson($error)
	{
		$this->returnJson(array('error' => $error));
	}

	/**
	 * Checks if a controller has overridden allowAnonymous either as an array with actions to allow anonymous access to
	 * or as a bool that applies to all actions.
	 *
	 * @return bool
	 */
	public function beforeAction()
	{
		if (is_array($this->allowAnonymous))
		{
			if (!preg_grep("/{$this->getAction()->id}/i", $this->allowAnonymous))
			{
				$this->requireLogin();
			}
		}
		elseif (is_bool($this->allowAnonymous))
		{
			if ($this->allowAnonymous == false)
			{
				$this->requireLogin();
			}
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function filters()
	{
	}
}
