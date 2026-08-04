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
 * @mixin WidgetTrait
 * @mixin SavableComponentTrait
 * @mixin Model
 * @phpstan-require-extends Widget
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface WidgetInterface extends SavableComponentInterface
{
    /**
     * Returns the widget’s SVG icon, if it has one.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     *
     * @return string|null
     * @since 3.2.0
     */
    public static function icon(): ?string;

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return int|null The widget’s maximum colspan, if it has one
     */
    public static function maxColspan(): ?int;

    /**
     * Returns the widget’s title.
     *
     * @return string|null The widget’s title.
     */
    public function getTitle(): ?string;

    /**
     * Returns the widget’s subtitle.
     *
     * @return string|null The widget’s subtitle
     * @since 3.4.0
     */
    public function getSubtitle(): ?string;

    /**
     * Returns the widget's body HTML.
     *
     * @return string|null The widget’s body HTML, or `null` if the widget
     * should not be visible. (If you don’t want the widget to be selectable in
     * the first place, use [[isSelectable()]].)
     */
    public function getBodyHtml(): ?string;
}
