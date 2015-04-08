<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\variables;

/**
 * ToolInfo represents a tool class, making information about it available to the templates.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ToolInfo extends ComponentInfo
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		/** @var ToolInterface $component */
		$component = $this->component;
		return $component::iconValue();
	}

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		/** @var ToolInterface $component */
		$component = $this->component;
		return $component::optionsHtml();
	}

	/**
	 * Returns the tool's button label.
	 *
	 * @return string
	 */
	public function getButtonLabel()
	{
		/** @var ToolInterface $component */
		$component = $this->component;
		return $component::buttonLabel();
	}
}
