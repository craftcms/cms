<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Image\Data\ImageColors;

/**
 * @since 6.0.0
 */
readonly class AssetIngest
{
    /**
     * @param  Asset|null  $asset  A prepared, unsaved asset. Upload source, colors, sanitization, filename, MIME type,
     *                             location, uploader, conflict behavior, and validation scenario are owned by the handler.
     */
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
        public ?Asset $asset = null,
    ) {}
}
