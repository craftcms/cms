<?php
namespace Craft;

/**
 * Widget template variable.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.variables
 * @since     1.0
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
