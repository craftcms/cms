<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility;

use CraftCms\Cms\Component\Contracts\ComponentInterface;

abstract class Utility implements ComponentInterface
{
    /**
     * Returns the utility’s unique identifier.
     *
     * The ID should be in `kebab-case`, as it will be visible in the URL (`admin/utilities/the-handle`).
     */
    abstract public static function id(): string;

    /**
     * Returns the utility’s SVG icon if it has one.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `src/icons/solid/`.
     */
    public static function icon(): ?string
    {
        return null;
    }

    /**
     * Returns the number that should be shown in the utility’s nav item badge.
     *
     * If `0` is returned, no badge will be shown
     */
    public static function badgeCount(): int
    {
        return 0;
    }

    /**
     * Returns the utility's content HTML.
     */
    abstract public static function contentHtml(): string;

    /**
     * Returns the utility's content HTML.
     */
    public static function toolbarHtml(): string
    {
        return '';
    }

    /**
     * Returns the utility’s toolbar HTML.
     */
    public static function footerHtml(): string
    {
        return '';
    }

    #[\Override]
    public static function isSelectable(): bool
    {
        return true;
    }
}
