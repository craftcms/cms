<?php
namespace Blocks;

/**
 *
 */
abstract class BaseController extends \CController
{
	/**
	 * Returns the folder containing view files for this controller.
	 * We're overriding this since CController's version defaults $module to blx().
	 *
	 * @return string The folder containing the view files for this controller.
	 */
	public function getViewPath()
	{
		if (($module = $this->getModule()) === null)
			$module = blx();

		return $module->getViewPath().'/';
	}

	/**
	 * Renders and outputs the template requested by the URL
	 * and sets the Content-Type header based on the URL extension.
	 *
	 * @param array $variables
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
					throw new HttpException(404);
				else
					throw $e;
			}

			// Set the Content-Type header
			$mimeType = blx()->request->getMimeType();
			header('Content-Type: '.$mimeType);

			// Output to the browser!
			echo $output;
		}
		else
			throw new HttpException(404);
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
		if (($output = TemplateHelper::render($template, $variables)) !== false)
		{
			if ($processOutput)
				$output = $this->processOutput($output);

			if ($return)
				return $output;
			else
				echo $output;
		}
		else
			throw new HttpException(404);
	}

	/**
	 * Redirects user to the login template if they're not logged in
	 */
	public function requireLogin()
	{
		if (blx()->user->isGuest())
			blx()->user->loginRequired();
	}

	/**
	 * Requires the current user to be logged in as an admin
	 */
	public function requireAdmin()
	{
		if (!blx()->accounts->getCurrentUser()->admin)
			throw new HttpException(403, Blocks::t('This action may only be performed by admins.'));
	}

	/**
	 * Returns a 404 if this isn't a POST request
	 * @throws HttpException
	 */
	public function requirePostRequest()
	{
		if (!blx()->config->devMode && blx()->request->getRequestType() !== 'POST')
			throw new HttpException(404);
	}

	/**
	 * Returns a 404 if this isn't an Ajax request
	 * @throws HttpException
	 */
	public function requireAjaxRequest()
	{
		if (!blx()->config->devMode && !blx()->request->isAjaxRequest())
			throw new HttpException(404);
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
			$url = UrlHelper::generateUrl($url);

		if ($url !== null)
			parent::redirect($url, $terminate, $statusCode);
	}

	/**
	 * Redirects to the URI specified in the POST. If no URL is specified, redirects to the current requ
	 */
	public function redirectToPostedUrl()
	{
		$url = blx()->request->getPost('redirect');

		if ($url === null)
			$url = blx()->request->getPath();

		$this->redirect($url);
	}

	/**
	 * Respond with JSON
	 *
	 * @param array $r The array to JSON-encode and return
	 */
	public function returnJson($r)
	{
		Json::sendJsonHeaders();
		echo Json::encode($r);
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
	 * @return array
	 */
	public function filters()
	{
	}
}
