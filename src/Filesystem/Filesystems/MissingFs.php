<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use CraftCms\Cms\Component\Concerns\MissingComponentTrait;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use Override;
use RuntimeException;

class MissingFs extends Filesystem implements MissingComponentInterface
{
    use MissingComponentTrait;

    #[Override]
    public function getDiskConfig(): array
    {
        throw new RuntimeException('Missing filesystem components do not provide a disk config.');
    }
}
