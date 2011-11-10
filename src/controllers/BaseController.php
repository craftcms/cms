<?php

class BaseController extends CController
{

	private $_widgetStack = array();

	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/column1';

	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu = array();

	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs = array();

	public function filterVersionCheck($filterChain)
	{
		$filter = new VersionCheckFilter();
		$filter->filter($filterChain);
	}

	public function filterHttps($filterChain)
	{
		$filter = new HttpsFilter();
		$filter->filter($filterChain);
	}

	public function filterConfigCheck($filterChain)
	{
		$filter = new ConfigCheckFilter();
		$filter->filter($filterChain);
	}

	public function filters()
	{
		return array(
			'ConfigCheck',
			'VersionCheck',
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

		$path = $module->getViewPath();
		return $path;
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

		$viewFile = str_replace('\\', '/', $viewFile);

		if (($renderer = Blocks::app()->getViewRenderer()) !== null)
		{
			if (get_class($renderer) == 'BlocksTemplateRenderer')
			{
				foreach (Blocks::app()->site->getAllowedTemplateFileExtensions() as $allowedExtension)
				{
					if(is_file($viewFile.'.'.$allowedExtension))
					{
						$extension = $allowedExtension;
						break;
					}
				}
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

			if (($requestController = $this->getRequestController()) !== null)
				$layoutFile = $requestController->getLayoutFile($requestController->layout);
			else
				$layoutFile = $this->getLayoutFile($this->layout);

			if ($layoutFile !== false)
				$output = $this->renderFile($layoutFile, array('content' => $output), true);

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
			$extension = Blocks::app()->file->set($viewFile, false)->getExtension();
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
			throw new BlocksException(Blocks::t('yii','{controller} contains improperly nested widget tags in its view "{view}". A {widget} widget does not have an endWidget() call.', array('{controller}' => get_class($this), '{view}' => $viewFile, '{widget}' => get_class($widget))));
		}
	}
}
