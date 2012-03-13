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
			$viewPath = rtrim(b()->path->emailTemplatePath, '/');
			$viewName = substr($viewName, 9);
		}
		else
			$viewPath = $this->getViewPath();

		return $this->resolveViewFile($viewName, $viewPath, $basePath, $moduleViewPath);
	}

	/**
	 * Loads the requested template
	 *
	 * @param array $tags
	 */
	public function loadRequestedTemplate($tags = array())
	{
		b()->urlManager->processTemplateMatching();
		$templateMatch = b()->urlManager->templateMatch;

		// see if we can match a template on the file system.
		if ($templateMatch !== null)
		{
			$template = $templateMatch->getRelativePath().'/'.$templateMatch->getFileName();
			$tags = array_merge(b()->urlManager->templateTags, $tags);
			$this->loadTemplate($template, $tags);

		}
		else
			throw new HttpException(404);
	}

	/**
	 * Loads a template
	 *
	 * @param       $templatePath
	 * @param array $tags Any variables that should be available to the template
	 * @param bool  $return
	 * @param bool  $return Whether to return the results, rather than output them
	 * @return mixed
	 */
	public function loadTemplate($templatePath, $vars = array(), $return = false)
	{
		$templatePath = TemplateHelper::resolveTemplatePath($templatePath);
		if ($templatePath !== false)
		{
			$tags = array();

			if (is_array($vars))
			{
				foreach ($vars as $name => $var)
				{
					$tags[$name] = new Tag($var);
				}
			}

			return $this->renderPartial($templatePath, $tags, $return);
		}

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
	 * @param $viewFile
	 * @param null $data
	 * @param bool $return
	 * @return mixed|string
	 * @throws Exception
	 */
	public function renderFile($viewFile, $data = null, $return = false)
	{
		$widgetCount = count($this->_widgetStack);

		if (strpos($viewFile, 'email_templates') !== false)
		{
			$emailRenderer = new EmailTemplateRenderer();
			$content = $emailRenderer->renderFile($this, $viewFile, $data, $return);
		}
		else
		{
			if (($renderer = b()->getViewRenderer()) !== null && $renderer->fileExtension === '.'.\CFileHelper::getExtension($viewFile))
				$content = $renderer->renderFile($this, $viewFile, $data, $return);
			else
				$content = $this->renderInternal($viewFile, $data, $return);
		}

		if (count($this->_widgetStack) === $widgetCount)
			return $content;
		else
		{
			$widget = end($this->_widgetStack);
			throw new Exception(Blocks::t('blocks','{controller} contains improperly nested widget tags in its view "{view}". A {widget} widget does not have an endWidget() call.',
				array('{controller}' => get_class($this), '{view}' => $viewFile, '{widget}' => get_class($widget))));
		}
	}

	/**
	 * Redirects user to the login template if they're not logged in
	 */
	public function requireLogin()
	{
		if (b()->user->isGuest)
			b()->user->loginRequired();
	}

	/**
	 * Returns a 404 if this isn't a POST request
	 */
	public function requirePostRequest()
	{
		if (!b()->config->devMode && b()->request->requestType !== 'POST')
			throw new HttpException(404);
	}

	/**
	 * Returns a 404 if this isn't an Ajax request
	 */
	public function requireAjaxRequest()
	{
		if (!b()->config->devMode && !b()->request->isAjaxRequest)
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

		parent::redirect($url, $terminate, $statusCode);
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
	 * @return array
	 */
	public function filters()
	{

	}
}
