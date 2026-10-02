<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

/**
 * ComponentInterface defines the common interface to be implemented by Craft component classes.
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
