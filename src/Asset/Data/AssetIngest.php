<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Image\Data\ImageColors;

/**
 * @since 6.0.0
 */
readonly class AssetIngest
{
    public function __construct(
        public UploadedFile $source,
        public string $filename,
        public string $mimeType,
        public VolumeFolder $folder,
        public bool $sanitizeOnUpload,
        public ?ElementConditionInterface $selectionCondition = null,
        public ?VolumeFolder $temporaryFolder = null,
        public ?ImageColors $colors = null,
        public ?int $uploaderId = null,
    ) {}
}
