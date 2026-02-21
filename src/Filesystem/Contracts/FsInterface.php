<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

use craft\base\ModelInterface;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Component\Contracts\SavableComponentInterface;
use CraftCms\Cms\Validation\Contracts\Validatable;

/**
 * FsInterface defines the common interface to be implemented by filesystem classes.
 */
interface FsInterface extends BaseFsInterface, ConfigurableComponentInterface, ModelInterface, SavableComponentInterface, Validatable
{
    /**
     * Returns whether the “Files in this filesystem have public URLs” setting should be shown.
     */
    public function getShowHasUrlSetting(): bool;

    /**
     * Returns whether the “Base URL” setting should be shown.
     */
    public function getShowUrlSetting(): bool;
}
