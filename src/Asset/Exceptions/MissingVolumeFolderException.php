<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Exceptions;

use CraftCms\Cms\Asset\Data\AssetIndexEntry;
use CraftCms\Cms\Asset\Data\Volume;
use Throwable;

class MissingVolumeFolderException extends AssetException
{
    public function __construct(
        public AssetIndexEntry $indexEntry,
        public Volume $volume,
        public string $folderName,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
