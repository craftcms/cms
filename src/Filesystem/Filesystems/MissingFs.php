<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use RuntimeException;

final class MissingFs extends Filesystem implements MissingComponentInterface
{
    use MissingComponentTrait;

    #[\Override]
    public function getDiskConfig(): array
    {
        throw new RuntimeException('Missing filesystem components do not provide a disk config.');
    }
}
