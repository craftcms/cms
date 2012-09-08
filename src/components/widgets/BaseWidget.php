<?php
namespace Blocks;

/**
 * Widget base class
 */
abstract class BaseWidget extends BaseComponent implements IWidget
{
	protected $componentType = 'Widget';
	protected $settingsColumn = 'settings';

	/**
	 * Returns the widget's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return null;
	}

	/**
	 * Returns the widget's widget HTML.
	 *
	 * @return string
	 */
	public function getWidgetHtml()
	{
		return TemplateHelper::render('dashboard/_widget', array(
			'class' => $this->getClassHandle(),
			'title' => $this->getTitle(),
			'body'  => $this->getBodyHtml()
		));
	}

	/**
	 * Gets the widget's title.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getTitle()
	{
		// Default to the widget name
		return $this->getName();
	}

	/**
	 * Gets the widget's body HTML.
	 *
	 * @abstract
	 * @access protected
	 * @return string
	 */
	abstract protected function getBodyHtml();
}
