<?php

class BaseController extends CController
{
	private $_widgetStack = array();
	private $_defaultTemplateTags;

	public function filterHttps($filterChain)
	{
		$filter = new HttpsFilter();
		$filter->filter($filterChain);
	}

	public function filters()
	{
		return array(
			//'Https',
		);
	}

	private $_requestController = null;

	public function getRequestController()
	{
		return $this->_requestController;
	}

	public function setRequestController($requestController)
	{
		$this->_requestController = $requestController;
	}

	public function getViewFile($viewName)
	{
		if (($theme = Blocks::app()->getTheme()) !== null && ($viewFile = $theme->getViewFile($this, $viewName)) !== false)
			return $viewFile;

		$moduleViewPath = $basePath = Blocks::app()->getViewPath();

		if (($requestController = $this->getRequestController()) !== null)
			$module = $requestController->getModule();
		else
			$module = $this->getModule();

		if ($module !== null)
			$moduleViewPath = $module->getViewPath();

		return $this->resolveViewFile($viewName, $this->getViewPath(), $basePath, $moduleViewPath);
	}

	public function getViewPath()
	{
		if (($requestController = $this->getRequestController()) !== null)
			$module = $requestController->getModule();
		else
			$module = $this->getModule();

		if ($module === null)
			$module = Blocks::app();

		return $module->getViewPath();
	}

	public function resolveViewFile($viewName, $viewPath, $basePath, $moduleViewPath = null)
	{
		if (empty($viewName))
			return false;

		$extension = null;

		if ($moduleViewPath === null)
			$moduleViewPath = $basePath;

		if ($viewName[0] === '/')
		{
			if (strncmp($viewName, '//', 2) === 0)
				$viewFile = $basePath.$viewName;
			else
				$viewFile = $moduleViewPath.$viewName;
		}
		else if (strpos($viewName, '.'))
			$viewFile = Blocks::getPathOfAlias($viewName);
		else
			$viewFile = $viewPath.$viewName;

		$viewFile = Blocks::app()->path->normalizeDirectorySeparators($viewFile);

		if (($renderer = Blocks::app()->getViewRenderer()) !== null)
		{
			if (get_class($renderer) == 'BlocksTemplateRenderer')
			{
				if (($matchedTemplate = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($viewFile)) !== null)
					$extension = pathinfo($matchedTemplate, PATHINFO_EXTENSION);
			}
			else
				$extension = $renderer->fileExtension;
		}
		else
		{
			$extension = 'html';
		}

		if (is_file($viewFile.'.'.$extension))
			return Blocks::app()->findLocalizedFile($viewFile.'.'.$extension);
		else if ($extension !== '.php' && is_file($viewFile.'.php'))
			return Blocks::app()->findLocalizedFile($viewFile.'.php');
		else
			return false;
	}

	public function render($view, $data = null, $return = false)
	{
		if ($this->beforeRender($view))
		{
			$output = $this->renderPartial($view, $data, true);

			$this->afterRender($view, $output);

			$output = $this->processOutput($output);

			if ($return)
				return $output;
			else
				echo $output;
		}
	}

	public function renderFile($viewFile, $data = null, $return = false)
	{
		$widgetCount = count($this->_widgetStack);
		if (($renderer = Blocks::app()->getViewRenderer()) !== null)
		{
			$extension = pathinfo($viewFile, PATHINFO_EXTENSION);
			if ((get_class($renderer) === 'BlocksTemplateRenderer' && in_array($extension, Blocks::app()->site->getAllowedTemplateFileExtensions()) || $renderer->fileExtension === '.'.$extension))
				$content = $renderer->renderFile($this, $viewFile, $data, $return);
		}
		else
			$content = $this->renderInternal($viewFile, $data, $return);

		if (count($this->_widgetStack) === $widgetCount)
			return $content;
		else
		{
			$widget = end($this->_widgetStack);
			throw new BlocksException(Blocks::t('blocks','{controller} contains improperly nested widget tags in its view "{view}". A {widget} widget does not have an endWidget() call.', array('{controller}' => get_class($this), '{view}' => $viewFile, '{widget}' => get_class($widget))));
		}
	}

	/**
	 * Convert all template vars to tags before sending them to the template
	 */
	public function loadTemplate($templatePath, $data = array(), $return = false)
	{
		if (!is_array($data))
			$data = array();

		foreach ($data as &$tag)
		{
			$tag = TemplateHelper::getVarTag($tag);
		}

		return $this->render($templatePath, $data, $return);
	}
}
