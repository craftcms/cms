<?php
namespace Craft;

/**
 * Tool template variable.
 *
 * @package craft.app.validators
 */
class ToolVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return $this->component->getIconValue();
	}

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return $this->component->getOptionsHtml();
	}

	/**
	 * Returns the tool's button label.
	 *
	 * @return string
	 */
	public function getButtonLabel()
	{
		return $this->component->getButtonLabel();
	}
}
