<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\widgets;

use craft\app\components\SavableComponentTypeInterface;

/**
 * Interface Widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface WidgetInterface extends SavableComponentTypeInterface
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
	 * Returns the widget's body HTML.
	 *
	 * @return string|false The widget’s body HTML, or `false` if the widget
	 *                      should not be visible.
	 */
	public function getBodyHtml();

	/**
	 * Returns the widget's colspan.
	 *
	 * @return int The widget’s colspan.
	 */
	public function getColspan();
}
