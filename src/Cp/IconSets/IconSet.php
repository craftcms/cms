<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\IconSets;

use CraftCms\Cms\Cp\Events\IconSetsResolving;

/**
 * IconSet is the base class for custom icon sets, which can be registered via
 * the {@see IconSetsResolving} event.
 *
 * Icons selected from a custom icon set are stored as `set-handle:icon-name`.
 *
 * @since 5.12.0
 */
abstract class IconSet
{
    public function __construct(
        /**
         * The icon set’s handle, which prefixes the stored values of icons selected from it.
         *
         * Handles must begin with a letter, and can only contain letters, numbers, underscores, and hyphens.
         */
        public string $handle,
        /** The icon set’s name, as it should be shown in the control panel. */
        public string $name,
    ) {}

    /**
     * Returns the names of the icons in the set.
     *
     * Icon names can’t contain slashes, backslashes, colons, or angle brackets.
     *
     * @return string[]
     */
    abstract public function getIcons(): array;

    /**
     * Returns search keywords for an icon, in addition to its name.
     *
     * @param  string  $icon  The icon name
     * @return string[]
     */
    public function getKeywords(string $icon): array
    {
        return [];
    }

    /**
     * Returns an icon’s SVG markup, or `null` if the icon doesn’t exist.
     *
     * @param  string  $icon  The icon name
     */
    abstract public function getSvg(string $icon): ?string;
}
