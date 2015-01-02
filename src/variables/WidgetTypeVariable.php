<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

/**
 * Widget template variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class WidgetTypeVariable extends BaseComponentTypeVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->component->getTitle();
	}

	/**
	 * Returns the widget's colspan.
	 *
	 * @return int
	 */
	public function getColspan()
	{
		return $this->component->getColspan();
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		return $this->component->getBodyHtml();
	}
}
