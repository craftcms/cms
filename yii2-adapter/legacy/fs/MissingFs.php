<?php

declare(strict_types=1);

namespace craft\fs;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use CraftCms\Yii2Adapter\Filesystem\DiskFs;
use Override;
use RuntimeException;

/**
 * MissingFs represents a filesystem with an invalid class.
 *
 * @since 4.0.0
 * @deprecated 6.0.0
 */
class MissingFs extends DiskFs implements MissingComponentInterface
{
    use MissingComponentTrait;

    #[Override]
    public function getRootUrl(): ?string
    {
        return null;
    }

    #[Override]
    public function getDiskConfig(): array
    {
        throw new RuntimeException('Missing filesystem components do not provide a disk config.');
    }
}
