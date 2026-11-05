<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\Raster;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Files\Image;

/**
 * Prepares images of assets to send to AI models: scaled down, since models don’t look at more detail than that, and
 * rasterized if they’re SVGs.
 *
 * The image is returned as base64, so it can be stored with a conversation and sent again with its later messages.
 *
 * @since 6.0.0
 */
readonly class ModelImages
{
    /** The longest side of the images sent to models, in pixels. */
    public const int MAX_SIZE = 1568;

    public function __construct(
        private Images $images,
    ) {}

    /**
     * Returns an asset’s image as models get it, named after the asset and its file’s modification date, so a changed
     * file has a different name.
     */
    public function forAsset(Asset $asset): Base64Image
    {
        $copy = $asset->getCopyOfFile();

        try {
            return $this->forFile($copy)->as(self::name($asset));
        } finally {
            File::delete($copy);
        }
    }

    /**
     * Returns the image at a path as models get it. The file is left in place.
     */
    public function forFile(string $path): Base64Image
    {
        $isJpeg = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true);
        $prepared = AssetsHelper::tempFilePath($isJpeg ? 'jpg' : 'png');

        try {
            $image = $this->images->loadImage($path, rasterize: true);

            if ($image instanceof Raster) {
                $image->scaleToFit(self::MAX_SIZE, self::MAX_SIZE, false);
                $image->saveAs($prepared);
                $path = $prepared;
                $mimeType = $isJpeg ? 'image/jpeg' : 'image/png';
            } else {
                $mimeType = File::mimeType($path) ?: null;
            }

            return Image::fromBase64(base64_encode((string) file_get_contents($path)), $mimeType);
        } finally {
            File::delete($prepared);
        }
    }

    /**
     * Returns the name {@see forAsset()} gives an asset’s image.
     */
    public static function name(Asset $asset): string
    {
        return "asset-{$asset->id}-{$asset->dateModified?->getTimestamp()}";
    }
}
