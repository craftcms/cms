<?php
namespace Craft;

/**
 * Interface IWidget
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
	 * @return string The widget’s title.
	 */
	public function getTitle();

	/**
	 * Returns the path to the widget’s SVG icon.
	 *
	 * @return string The path to the widget’s SVG icon
	 */
	public function getIconPath();

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string|false The widget’s body HTML, or `false` if the widget
	 *                      should not be visible.
	 */
	public function getBodyHtml();

	/**
	 * Returns the widget's maximum colspan.
	 *
	 * @return int The widget’s maximum colspan.
	 */
	public function getMaxColspan();
}
