<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Filesystem;

use craft\base\FsInterface;

/** @deprecated Yii compatibility only. */
class LegacyFilesystemRenamed
{
    public function __construct(
        public FsInterface $filesystem,
    ) {
    }
}
