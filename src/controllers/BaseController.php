<?php
namespace Blocks;

/**
 *
 */
abstract class BaseController extends \CController
{
	private $_widgetStack = array();

	/**
	 * Returns the directory containing view files for this controller.
	 * We're overriding this since CController's version defaults $module to blx().
	 * @return string the directory containing the view files for this controller.
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
	 */
	public function renderRequestedTemplate($variables = array())
	{
		if (($templatePath = blx()->urlManager->processTemplateMatching()) !== false)
		{
			$variables = array_merge(blx()->urlManager->getTemplateVariables(), $variables);
			$output = $this->renderTemplate($templatePath, $variables, true);

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
	 * @param string $templatePath
	 * @param array $variables Variables to be passed to the template
	 * @param bool $return Whether to return the results, rather than output them
	 * @param bool  $processOutput
	 * @throws HttpException
	 * @return mixed
	 */
	public function renderTemplate($templatePath, $variables = array(), $return = false, $processOutput = false)
	{
		$variables['blx'] = new BlxVariable();
		$variables = TemplateHelper::prepTemplateVariables($variables);

		// Share the same FileTemplateProcessor instance for the whole request.
		$renderer = blx()->getViewRenderer();

		if (($output = $renderer->process($this, $templatePath, $variables, true)) !== false)
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
	 * @param Email $email
	 * @param array $variables
	 * @throws Exception
	 * @return mixed
	 */
	public function loadEmailTemplate(Email $email, $variables = array())
	{
		$variables = TemplateHelper::prepTemplateVariables($variables);

		$renderer = new EmailTemplateProcessor();

		if (($content = $renderer->process($this, $email, $variables)) !== false)
		{
			return $content;
		}
		else
			throw new Exception(Blocks::t(TranslationCategory::Email, 'Could not find the requested email template.'));
	}

	/**
	 * Redirects user to the login template if they're not logged in
	 */
	public function requireLogin()
	{
		if (blx()->user->getIsGuest())
			blx()->user->loginRequired();
	}

	/**
	 * Requires the current user to be logged in as an admin
	 */
	public function requireAdmin()
	{
		if (!blx()->users->getCurrentUser()->admin)
			throw new Exception(Blocks::t(TranslationCategory::App, 'This action may only be performed by admins.'));
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
		if (!blx()->config->devMode && !blx()->request->getIsAjaxRequest())
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
