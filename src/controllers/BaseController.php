<?php

/**
 *
 */
abstract class BaseController extends CController
{
	private $_widgetStack = array();

	/**
	 * @param $filterChain
	 */
	public function filterHttps($filterChain)
	{
		$filter = new HttpsFilter();
		$filter->filter($filterChain);
	}

	/**
	 * @return array
	 */
	public function filters()
	{
		return array(
			//'Https',
		);
	}

	/**
	 * @return mixed
	 */
	public function getViewPath()
	{
		if (($module = $this->getModule()) === null)
			$module = Blocks::app();

		return $module->getViewPath().DIRECTORY_SEPARATOR;
	}

	/**
	 * @param      $viewName
	 * @param      $viewPath
	 * @param      $basePath
	 * @param null $moduleViewPath
	 * @return bool
	 */
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

		if (($renderer = Blocks::app()->viewRenderer) !== null)
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

	/**
	 * @param      $view
	 * @param null $data
	 * @param bool $return
	 * @return mixed
	 */
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

	/**
	 * @param      $viewFile
	 * @param null $data
	 * @param bool $return
	 * @return mixed
	 * @throws BlocksException
	 */
	public function renderFile($viewFile, $data = null, $return = false)
	{
		$widgetCount = count($this->_widgetStack);
		if (($renderer = Blocks::app()->viewRenderer) !== null)
		{
			$extension = pathinfo($viewFile, PATHINFO_EXTENSION);
			if ((get_class($renderer) === 'BlocksTemplateRenderer' && in_array($extension, Blocks::app()->site->allowedTemplateFileExtensions) || $renderer->fileExtension === '.'.$extension))
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
	 * @param       $templatePath
	 * @param array $data
	 * @param bool  $return
	 * @return mixed
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
