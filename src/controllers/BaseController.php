<?php

class BaseController extends CController
{
	private $_widgetStack = array();
	private $_defaultTemplateTags = null;

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

	public function loadTemplate($templatePath, $data = array(), $return = false)
	{
		$data = array_merge($this->getDefaultTemplateTags(), $data);
		return $this->render($templatePath, $data, $return);
	}

	public function getDefaultTemplateTags()
	{
		if ($this->_defaultTemplateTags !== null)
			return $this->_defaultTemplateTags;

		$defaultTags = null;
		$site = Blocks::app()->request->getSiteInfo();
		if ($site !== null)
		{
			$defaultTags = array(
				'content' => new ContentTag($site->id),
				'assets' => new AssetsTag($site->id),
				'membership' => new MembershipTag($site->id),
				'security' => new SecurityTag($site->id),
				'resource' => new ResourceTag(),
				'url' => new UrlTag(),
			);

			// if it's a CP request, add the CP tag.
			if (Blocks::app()->request->getCMSRequestType() == RequestType::ControlPanel)
			{
				Blocks::import('application.business.tags.cp.*');
				$defaultTags['cp'] = new CpTag($site->id);
			}
		}

		$this->_defaultTemplateTags = $defaultTags;
		return $this->_defaultTemplateTags == null ? array() : $this->_defaultTemplateTags;
	}
}
