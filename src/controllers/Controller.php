<?php
namespace Blocks;

/**
 *
 */
abstract class Controller extends \CController
{
	private $_widgetStack = array();

	/**
	 * Returns the directory containing view files for this controller.
	 * We're overriding this since CController's version defaults $module to b().
	 * @return string the directory containing the view files for this controller.
	 */
	public function getViewPath()
	{
		if (($module = $this->getModule()) === null)
			$module = b();

		return $module->getViewPath().'/';
	}

	/**
	 * Overriding so we can check if the viewName is a request for an email template vs. a site template.
	 * @param $viewName
	 * @return mixed
	 */
	public function getViewFile($viewName)
	{
		if (($theme = b()->getTheme()) !== null && ($viewFile = $theme->getViewFile($this, $viewName)) !== false)
			return $viewFile;

		$moduleViewPath = $basePath = b()->getViewPath();
		if (($module = $this->getModule()) !== null)
			$moduleViewPath = $module->getViewPath();

		if (strncmp($viewName,'///email', 8) === 0)
		{
			$viewPath = rtrim(b()->path->getEmailTemplatesPath(), '/');
			$viewName = substr($viewName, 9);
		}
		else
			$viewPath = $this->getViewPath();

		$viewPath = str_replace('//', '/', $viewPath);

		return $this->resolveViewFile($viewName, $viewPath, $basePath, $moduleViewPath);
	}

	/**
	 * Loads the requested template
	 * @param array $variables
	 * @throws HttpException
	 */
	public function loadRequestedTemplate($variables = array())
	{
		if (($path = b()->urlManager->processTemplateMatching()) !== false)
		{
			$this->loadTemplate($path, $variables);
		}
		else
			throw new HttpException(404);
	}

	/**
	 * Loads a template
	 * @param       $templatePath
	 * @param array $vars
	 * @param bool  $return Whether to return the results, rather than output them
	 * @param bool  $processOutput
	 * @throws HttpException
	 * @return mixed
	 */
	public function loadTemplate($templatePath, $vars = array(), $return = false, $processOutput = false)
	{
		$variables = $this->processTemplateVariables($vars);

		if (($output = $this->processTemplatePath($templatePath, $variables, true)) !== false)
		{
			if($processOutput)
				$output = $this->processOutput($output);

			if($return)
				return $output;
			else
				echo $output;
		}
		else
			throw new HttpException(404);
	}

	/**
	 * @param $relativeTemplatePath
	 * @param array $data
	 * @return mixed
	 */
	public function loadEmailTemplate($relativeTemplatePath, $data = array())
	{
		$relativeTemplatePath = '///email/'.$relativeTemplatePath;
		return $this->loadTemplate($relativeTemplatePath, $data, true);
	}

	/**
	 * @param      $templatePath
	 * @param null $data
	 * @param bool $return
	 * @throws Exception
	 * @return mixed|string
	 */
	public function processTemplatePath($templatePath, $data = null, $return = false)
	{
		$widgetCount = count($this->_widgetStack);

		//if (strpos($viewFile, 'email_templates') !== false)
		//{
		//	$emailRenderer = new EmailTemplateRenderer();
		//	$content = $emailRenderer->renderFile($this, $viewFile, $data, $return);
		//}
	//	else
	//	{
		if (($renderer = b()->getViewRenderer()) !== null)
		{
			// Process the template.
			if (($content = $renderer->process($this, $templatePath, $data, $return)) !== false)
			{
				if (count($this->_widgetStack) === $widgetCount)
				{
					// Get the extension so we can set the correct mime type in the response.
					$extension = TemplateHelper::getExtension($templatePath);
					if (($mimeType = \CFileHelper::getMimeTypeByExtension($extension)) === null)
						$mimeType = 'text/html';

					b()->request->setMimeType($mimeType);
					header('Content-Type: '.$mimeType);

					return $content;
				}
				else
				{
					$widget = end($this->_widgetStack);
					throw new Exception(Blocks::t('blocks','{controller} contains improperly nested widget variables in its view "{view}". A {widget} widget does not have an endWidget() call.',
						array('{controller}' => get_class($this), '{view}' => $templatePath, '{widget}' => get_class($widget))));
				}
			}
		}

		return false;
	}

	/**
	 * @param $vars
	 * @return array
	 */
	public function processTemplateVariables($vars)
	{
		$variables = array();

		$vars = array_merge(b()->urlManager->getTemplateVariables(), $vars);
		$vars['b'] = new BVariable;

		if (is_array($vars))
		{
			foreach ($vars as $name => $var)
			{
				$variables[$name] = TemplateHelper::getVariable($var);
			}
		}

		return $variables;
	}

	/**
	 * Redirects user to the login template if they're not logged in
	 */
	public function requireLogin()
	{
		if (b()->user->getIsGuest())
			b()->user->loginRequired();
	}

	/**
	 * Returns a 404 if this isn't a POST request
	 * @throws HttpException
	 */
	public function requirePostRequest()
	{
		if (!b()->config->devMode && b()->request->getRequestType() !== 'POST')
			throw new HttpException(404);
	}

	/**
	 * Returns a 404 if this isn't an Ajax request
	 * @throws HttpException
	 */
	public function requireAjaxRequest()
	{
		if (!b()->config->devMode && !b()->request->getIsAjaxRequest())
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
	 * Redirects to the URI specified in the POST
	 */
	public function redirectToPostedUrl()
	{
		$url = b()->request->getPost('redirect');
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
		b()->end();
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
