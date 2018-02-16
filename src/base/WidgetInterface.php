<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * WidgetInterface defines the common interface to be implemented by dashboard widget classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[WidgetTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface WidgetInterface extends SavableComponentInterface
{
    // Static
    // =========================================================================

    /**
     * Returns the path to the widget’s SVG icon.
     *
     * @return string|null The path to the widget’s SVG icon
     */
    public static function iconPath();

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return int|null The widget’s maximum colspan, if it has one
     */
    public static function maxColspan();

    // Public Methods
    // =========================================================================

    /**
     * Returns the widget’s title.
     *
     * @return string The widget’s title.
     */
    public function getTitle(): string;

    /**
     * Returns the widget's body HTML.
     *
     * @return string|false The widget’s body HTML, or `false` if the widget
     * should not be visible. (If you don’t want the widget to be selectable in
     * the first place, use [[isSelectable()]].)
     */
    public function getBodyHtml();
}
