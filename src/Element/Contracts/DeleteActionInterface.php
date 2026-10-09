<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

/**
 * DeleteActionInterface should be implemented by Delete element actions that
 * support hard deletion.
 *
 * [[setHardDelete()]] will only be invoked when viewing soft-deleted elements.
 */
interface DeleteActionInterface extends ElementActionInterface
{
    public function canHardDelete(): bool;

    public function setHardDelete(): void;
}
