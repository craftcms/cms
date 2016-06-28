<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\base;

use craft\app\elements\User;

/**
 * WidgetInterface defines the common interface to be implemented by dashboard widget classes.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[WidgetTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * Returns the path to the widget’s SVG icon.
     *
     * @return string|null The path to the widget’s SVG icon, if it has one
     */
    public function getIconPath();

    /**
     * Returns the widget’s body HTML.
     *
     * @return string|false The widget’s body HTML, or `false` if the widget
     *                      should not be visible.
     */
    public function getBodyHtml();

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return integer|null The widget’s maximum colspan, if it has one
     */
    public function getMaxColspan();
}
