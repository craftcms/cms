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
     * Returns the path to the utility’s SVG icon.
     *
     * @return string|null
     */
    public static function iconPath();

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
