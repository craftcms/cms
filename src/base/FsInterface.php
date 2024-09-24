<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * FsInterface defines the common interface to be implemented by filesystem classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[FsTrait]].
 *
 * @mixin Fs
 * @phpstan-require-extends Fs
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface FsInterface extends BaseFsInterface, SavableComponentInterface
{
    /**
     * Returns whether the “Files in this filesystem have public URLs” setting should be shown.
     *
     * @return bool
     * @since 4.5.0
     */
    public function getShowHasUrlSetting(): bool;

    /**
     * Returns whether the “Base URL” setting should be shown.
     *
     * If this returns `false`, and the filesystem has a base URL, [[getRootUrl()]] should be implemented directly,
     * rather than storing the base URL on the [[\craft\base\Fs::$url]] property.
     *
     * @return bool
     * @since 4.5.0
     */
    public function getShowUrlSetting(): bool;
}
