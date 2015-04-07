<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use craft\app\elements\User;

/**
 * WidgetInterface defines the common interface to be implemented by dashboard widget classes.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[WidgetTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface WidgetInterface extends SavableComponentInterface
{
	// Public Methods
	// =========================================================================

	/**
	 * Performs any actions before a widget is saved.
	 *
	 * @return boolean Whether the widget should be saved
	 */
	public function beforeSave();

	/**
	 * Performs any actions after a widget is saved.
	 */
	public function afterSave();

	/**
	 * Returns the widget’s title.
	 *
	 * @return string The widget’s title.
	 */
	public function getTitle();

	/**
	 * Returns the widget’s body HTML.
	 *
	 * @return string|false The widget’s body HTML, or `false` if the widget
	 *                      should not be visible.
	 */
	public function getBodyHtml();

	/**
	 * Returns the widget’s colspan.
	 *
	 * @return int The widget’s colspan.
	 */
	public function getColspan();

	/**
	 * Returns user that owns the widget
	 *
	 * @return User the user that owns the widget
	 */
	public function getUser();
}
