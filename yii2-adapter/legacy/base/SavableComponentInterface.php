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
     * @since 3.0.0
     * @deprecated 6.0.0
     */
    interface SavableComponentInterface extends ConfigurableComponentInterface
    {
        /**
         * Returns whether the component is new (unsaved).
         *
         * @return bool Whether the component is new
         */
        public function getIsNew(): bool;

        // Events
        // -------------------------------------------------------------------------

        /**
         * Performs actions before a component is saved.
         *
         * @param bool $isNew Whether the component is brand new
         * @return bool Whether the component should be saved
         */
        public function beforeSave(bool $isNew): bool;

        /**
         * Performs actions after a component is saved.
         *
         * @param bool $isNew Whether the component is brand new
         */
        public function afterSave(bool $isNew): void;

        /**
         * Performs actions before a component is deleted.
         *
         * @return bool Whether the component should be deleted
         */
        public function beforeDelete(): bool;

        /**
         * Performs actions before a component delete is applied to the database.
         *
         * @since 3.1.0
         */
        public function beforeApplyDelete(): void;

        /**
         * Performs actions after a component is deleted.
         */
        public function afterDelete(): void;
    }
}

class_alias(\CraftCms\Cms\Support\Contracts\SavableComponentInterface::class, SavableComponentInterface::class);
