<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ComponentInterface defines the common interface to be implemented by Craft component classes.
     *
     * A class implementing this interface should extend [[Model]].
     *
     * @mixin Model
     * @since 3.0.0
     * @deprecated 6.0.0
     */
    interface ComponentInterface extends ModelInterface
    {
        /**
         * Returns the display name of this class.
         *
         * @return string The display name of this class.
         */
        public static function displayName(): string;

        /**
         * Returns whether the component should be selectable in component Type selects.
         *
         * @return bool whether the component should be selectable in component Type selects.
         * @since 3.5.0
         */
        public static function isSelectable(): bool;
    }
}

class_alias(\CraftCms\Cms\Support\Contracts\ComponentInterface::class, ComponentInterface::class);
