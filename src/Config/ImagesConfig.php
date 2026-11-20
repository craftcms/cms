<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

/**
 * Settings for how Craft cleanses and transforms images.
 *
 * @since 6.0.0
 */
class ImagesConfig extends BaseConfig
{
    public const string DRIVER_AUTO = 'auto';

    public const string DRIVER_GD = 'gd';

    public const string DRIVER_IMAGICK = 'imagick';

    public const string DRIVER_VIPS = 'vips';

    /**
     * The image driver Craft should use to cleanse and transform images: `auto`, `imagick`, `vips`, or `gd`.
     *
     * `auto` uses ImageMagick if it’s installed, followed by libvips and GD.
     */
    public string $driver = self::DRIVER_AUTO;

    /** The quality level used when saving JPG and PNG files, from 1 (worst quality, smallest file) to 100 (best quality, biggest file). */
    public int $defaultQuality = 82;

    /**
     * @var array<string, float|int|string> The aspect ratios the image editor offers, as `label` => `ratio`.
     *
     * A ratio is a number, or `none` or `original`.
     */
    public array $editorRatios = [
        'Unconstrained' => 'none',
        'Original' => 'original',
        'Square' => 1,
        '16:9' => 1.78,
        '10:8' => 1.25,
        '7:5' => 1.4,
        '4:3' => 1.33,
        '5:3' => 1.67,
        '3:2' => 1.5,
    ];

    /** Whether Craft should favor reduced file sizes over lossless encoding where supported. */
    public bool $optimizeFilesize = true;

    /** Whether CMYK should be preserved as the colorspace when manipulating images. */
    public bool $preserveCmykColorspace = false;

    /** Whether EXIF data should be preserved when manipulating and uploading images. */
    public bool $preserveExifData = false;

    /**
     * Whether embedded color profiles (ICC) should be preserved when manipulating images.
     *
     * Setting this to `false` reduces image sizes a little, but can save images with an incorrect gamma value. It only has an
     * effect if the image driver supports embedded color profiles.
     */
    public bool $preserveColorProfiles = true;

    /** Whether Craft should rotate images according to their EXIF data on upload. */
    public bool $rotateOnUploadByExifData = true;

    /** Whether GIF files should be cleansed and transformed. */
    public bool $transformGifs = true;

    /** Whether SVG files should be transformed. */
    public bool $transformSvgs = true;

    /** Whether image transforms should upscale images smaller than the transform’s dimensions by default. */
    public bool $upscale = true;

    /** The maximum dimension to use when caching images from external sources for transforms, or `0` to never cache them. */
    public int $maxCachedCloudSize = 0;

    /**
     * The server path to an image file that should be sent when responding to an image request with a 404 status code.
     *
     * This can be set to an aliased path such as `@webroot/assets/404.svg`.
     */
    public ?string $brokenPath = null;

    public function driver(string $value): self
    {
        $this->driver = $value;

        return $this;
    }

    public function defaultQuality(int $value): self
    {
        $this->defaultQuality = $value;

        return $this;
    }

    /** @param array<string, float|int|string> $value */
    public function editorRatios(array $value): self
    {
        $this->editorRatios = $value;

        return $this;
    }

    public function optimizeFilesize(bool $value = true): self
    {
        $this->optimizeFilesize = $value;

        return $this;
    }

    public function preserveCmykColorspace(bool $value = true): self
    {
        $this->preserveCmykColorspace = $value;

        return $this;
    }

    public function preserveExifData(bool $value = true): self
    {
        $this->preserveExifData = $value;

        return $this;
    }

    public function preserveColorProfiles(bool $value = true): self
    {
        $this->preserveColorProfiles = $value;

        return $this;
    }

    public function rotateOnUploadByExifData(bool $value = true): self
    {
        $this->rotateOnUploadByExifData = $value;

        return $this;
    }

    public function transformGifs(bool $value = true): self
    {
        $this->transformGifs = $value;

        return $this;
    }

    public function transformSvgs(bool $value = true): self
    {
        $this->transformSvgs = $value;

        return $this;
    }

    public function upscale(bool $value = true): self
    {
        $this->upscale = $value;

        return $this;
    }

    public function maxCachedCloudSize(int $value): self
    {
        $this->maxCachedCloudSize = $value;

        return $this;
    }

    public function brokenPath(?string $value): self
    {
        $this->brokenPath = $value;

        return $this;
    }
}
