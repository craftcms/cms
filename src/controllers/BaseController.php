<?php

/**
 *
 */
abstract class BaseController extends CController
{
	/**
	 * Returns the directory containing view files for this controller.
	 * We're overriding this since CController's version defaults $module to Yii::app().
	 * @return string the directory containing the view files for this controller.
	 */
	public function getViewPath()
	{
		if (($module = $this->getModule()) === null)
			$module = Blocks::app();

		return $module->getViewPath().'/';
	}

	/**
	 * Loads a template
	 * @param string $template The template to load
	 * @param array $data Any variables that should be available to the templtae
	 * @param bool $return Whether to return the results, rather than output them
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

		return $this->renderPartial($templatePath, $data, $return);
	}
}
