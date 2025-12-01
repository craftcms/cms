<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

/**
 * Iconic defines the common interface to be implemented by components that
 * can have icons within the control panel.
 */
interface Iconic
{
    /**
     * Returns the component’s icon, if it has one.
     *
     * The returned icon can be a system icon’s name (e.g. `'whiskey-glass-ice'`),
     * the path to an SVG file, or raw SVG markup.
     *
     * System icons can be found in `resources/icons/solid/`.
     */
    public function getIcon(): ?string;
}
