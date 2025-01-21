<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * UtilityInterface defines the common interface to be implemented by utility classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface UtilityInterface extends ComponentInterface
{
    /**
     * Returns the utility’s unique identifier.
     *
     * The ID should be in `kebab-case`, as it will be visible in the URL (`admin/utilities/the-handle`).
     *
     * @return string
     */
    public static function id(): string;

    /**
     * Returns the utility’s SVG icon, if it has one.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     *
     * @return string|null
     * @since 5.0.0
     */
    public static function icon(): ?string;

    /**
     * Returns the number that should be shown in the utility’s nav item badge.
     *
     * If `0` is returned, no badge will be shown
     *
     * @return int
     */
    public static function badgeCount(): int;

    /**
     * Returns the utility's content HTML.
     *
     * @return string
     */
    public static function contentHtml(): string;

    /**
     * Returns the utility’s toolbar HTML.
     *
     * @return string
     * @since 3.4.0
     */
    public static function toolbarHtml(): string;

    /**
     * Returns the utility’s footer HTML.
     *
     * @return string
     * @since 3.4.0
     */
    public static function footerHtml(): string;
}
