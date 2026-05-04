<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * DeleteActionInterface should be implemented by Delete element actions that
     * support hard deletion.
     *
     * [[setHardDelete()]] will only be invoked when viewing soft-deleted elements.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.6.5
     * @mixin Delete
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Contracts\DeleteActionInterface} instead.
     */
    interface DeleteActionInterface extends \CraftCms\Cms\Element\Contracts\DeleteActionInterface
    {
    }
}

class_alias(\CraftCms\Cms\Element\Contracts\DeleteActionInterface::class, DeleteActionInterface::class);
