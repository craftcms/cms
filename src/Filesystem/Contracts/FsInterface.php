<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Component\Contracts\SavableComponentInterface;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Validation\Contracts\Validatable;

/**
 * FsInterface defines the common interface to be implemented by filesystem classes.
 *
 * @property string|null $name
 * @property string|null $handle
 * @property string|null $oldHandle
 * @property bool $hasUrls
 * @property string|null $url
 * @property string|null $uid
 *
 * @phpstan-require-extends Filesystem
 */
interface FsInterface extends ConfigurableComponentInterface, SavableComponentInterface, Validatable
{
    /**
     * Returns the Laravel disk configuration for this filesystem.
     *
     * @return array<string,mixed>
     */
    public function getDiskConfig(): array;

    /**
     * Returns whether the “Files in this filesystem have public URLs” setting should be shown.
     */
    public function getShowHasUrlSetting(): bool;

    /**
     * Returns whether the “Base URL” setting should be shown.
     */
    public function getShowUrlSetting(): bool;

    /**
     * Returns whether [[getSettingsHtml()]] returns legacy control panel HTML
     * (Twig markup that may register asset bundles and inline JS), as opposed
     * to a Vue component tag.
     *
     * Legacy settings are rendered as an isolated HTML island; component
     * settings are compiled as part of the Vue page.
     */
    public function hasLegacySettingsHtml(): bool;
}
