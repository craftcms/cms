<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Component\Contracts\SavableComponentInterface;

/**
 * FsInterface defines the common interface to be implemented by legacy filesystem classes.
 *
 * @mixin Fs
 * @phpstan-require-extends Fs
 * @since 4.0.0
 * @deprecated 6.0.0 Configure Laravel filesystem disks instead.
 */
interface FsInterface extends BaseFsInterface, SavableComponentInterface
{
    /**
     * Returns whether the “Files in this filesystem have public URLs” setting should be shown.
     */
    public function getShowHasUrlSetting(): bool;

    /**
     * Returns whether the “Base URL” setting should be shown.
     */
    public function getShowUrlSetting(): bool;

    /**
     * Returns the Laravel disk configuration used by the compatibility bridge.
     *
     * @return array<string,mixed>
     */
    public function getDiskConfig(): array;
}
