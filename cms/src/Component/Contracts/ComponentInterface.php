<?php

namespace CraftCms\Cms\Component\Contracts;

/**
 * ComponentInterface defines the common interface to be implemented by Craft component classes.
 *
 * @since 6.0.0
 */
interface ComponentInterface
{
    /**
     * Returns the display name of this class.
     */
    public static function displayName(): string;

    /**
     * Returns whether the component should be visible in the control panel.
     */
    public static function isSelectable(): bool;
}
