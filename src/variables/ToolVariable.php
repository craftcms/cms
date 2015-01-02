<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Tool template variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
