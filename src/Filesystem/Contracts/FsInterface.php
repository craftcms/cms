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
 * @property-read string|null $rootUrl
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
     * Returns the root URL for this filesystem, if it has one.
     */
    public function getRootUrl(): ?string;
}
