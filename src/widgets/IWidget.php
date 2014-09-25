<?php
namespace Craft;

/**
 * Interface IWidget
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.widgets
 * @since     1.0
 */
interface IWidget extends ISavableComponentType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the widget's title.
	 *
	 * @return string
	 */
	public function getTitle();

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string
	 */
	public function getBodyHtml();

	/**
	 * Returns the widget's colspan.
	 *
	 * @return int
	 */
	public function getColspan();
}
