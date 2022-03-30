<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

/**
 * DeleteActionInterface should be implemented by Delete element actions that
 * support hard deletion.
 *
 * [[setHardDelete()]] will only be invoked when viewing soft-deleted elements.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.5
 * @mixin Delete
 */
interface DeleteActionInterface
{
    /**
     * Returns whether the action is capable of hard-deleting elements.
     *
     * @return bool
     */
    public function canHardDelete(): bool;

    /**
     * Instructs the action that the elements should be hard-deleted.
     */
    public function setHardDelete(): void;
}
