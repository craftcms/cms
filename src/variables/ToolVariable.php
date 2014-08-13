<?php
namespace Craft;

/**
 * Tool template variable.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     1.0
 */
class ToolVariable extends BaseComponentTypeVariable
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
