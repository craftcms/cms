<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use craft\helpers\FileHelper;
use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Cms;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickException;
use Imagine\Exception\NotSupportedException;
use Imagine\Exception\RuntimeException;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\AbstractFont;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\ExifMetadataReader;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Imagick\Imagine as ImagickImagine;
use Throwable;

use function CraftCms\Cms\maxPowerCaptain;
use function CraftCms\Cms\t;

class Raster extends Image
{
    private ?string $_imageSourcePath = null;

    private ?string $_extension = null;

    private bool $_isAnimated = false;

    private int $_quality = 0;

    private ?ImageInterface $_image = null;

    private ?AbstractImagine $_instance = null;

    private ?RGB $_palette = null;

    private ?AbstractFont $_font = null;

    private ?ColorInterface $_fill = null;

    public function __construct($config = [])
    {
        $generalConfig = Cms::config();

        $extension = strtolower((string) $generalConfig->imageDriver);

        // If it's explicitly set, take their word for it.
        if ($extension === 'gd') {
            $this->_instance = new GdImagine;
        } else {
            if ($extension === 'imagick') {
                $this->_instance = new ImagickImagine;
            } else {
                // Let's try to auto-detect.
                if (app(Images::class)->getIsGd()) {
                    $this->_instance = new GdImagine;
                } else {
                    $this->_instance = new ImagickImagine;
                }
            }
        }

        $this->_quality = $generalConfig->defaultImageQuality;

        parent::__construct($config);
    }

    public function getImagineImage(): ?ImageInterface
    {
        return $this->_image;
    }

    public function getWidth(): int
    {
        return $this->_image->getSize()->getWidth();
    }

    public function getHeight(): int
    {
        return $this->_image->getSize()->getHeight();
    }

    public function getExtension(): string
    {
        return $this->_extension;
    }

    public function loadImage(string $path): self
    {
        $imageService = app(Images::class);

        if (! is_file($path)) {
            Log::error('Tried to load an image at '.$path.', but the file does not exist.', [__METHOD__]);
            throw new ImageException(t('No file exists at the given path.'));
        }

        if (! $imageService->checkMemoryForImage($path)) {
            throw new ImageException(t('Not enough memory available to perform this image operation.'));
        }

        // Make sure the image says it's an image
        $mimeType = FileHelper::getMimeType($path, null, false);

        if ($mimeType !== null && ! str_starts_with($mimeType, 'image/') && ! str_starts_with($mimeType, 'application/pdf')) {
            throw new ImageException(t('The file “{name}” does not appear to be an image.', [
                'name' => basename($path),
            ]));
        }

        try {
            $this->_image = $this->_instance->open($path);
        } catch (Throwable $e) {
            // Imagick can throw all sorts of errors via the open() method
            // we should log them to better know what's going on
            Log::info($e->getMessage(), ['file' => $e->getFile()]);
            if (($instanceException = $e->getPrevious()) !== null) {
                Log::info($instanceException->getMessage(), ['file' => $instanceException->getFile().':'.$instanceException->getLine()]);
            }
            throw new ImageException(t('The file “{name}” does not appear to be an image.', [
                'name' => basename($path),
            ]), 0, $e);
        }

        // For Imagick, convert CMYK to RGB, save and re-open.
        if (
            ! app(Images::class)->getIsGd()
            && ! Cms::config()->preserveCmykColorspace
            && $this->_image instanceof ImagickImage
            && method_exists($this->_image->getImagick(), 'getImageColorspace')
            && $this->_image->getImagick()->getImageColorspace() === Imagick::COLORSPACE_CMYK
            && method_exists($this->_image->getImagick(), 'transformImageColorspace')
        ) {
            $this->_image->getImagick()->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            $this->_image->save();

            /** @var self */
            return app(Images::class)->loadImage($path);
        }

        $this->_imageSourcePath = $path;
        $this->_extension = pathinfo($path, PATHINFO_EXTENSION);

        if (in_array($this->_extension, ['gif', 'webp'])) {
            if (! $imageService->getIsGd() && $this->_image->layers()) {
                $this->_isAnimated = true;
            }
        }

        return $this;
    }

    public function crop(int $x1, int $x2, int $y1, int $y2): self
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        if ($this->_isAnimated) {
            // Create a new image instance to avoid object references messing up our dimensions.
            $newSize = new Box($width, $height);
            $startingPoint = new Point($x1, $y1);
            $gif = $this->_instance->create($newSize);
            $gif->layers()->remove(0);

            $this->_image->layers()->coalesce();
            foreach ($this->_image->layers() as $layer) {
                $croppedLayer = $layer->crop($startingPoint, $newSize);
                $gif->layers()->add($croppedLayer);

                // Since it might take a while, send a heartbeat back
                $this->heartbeat();
            }

            $this->_image = $gif;
        } else {
            $this->_image->crop(new Point($x1, $y1), new Box($width, $height));
        }

        return $this;
    }

    public function scaleToFit(?int $targetWidth, ?int $targetHeight, ?bool $scaleIfSmaller = null): self
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);
        $width = $this->getWidth();
        $height = $this->getHeight();

        $scaleIfSmaller ??= Cms::config()->upscaleImages;

        if ($scaleIfSmaller || $width > $targetWidth || $height > $targetHeight) {
            // go with the provided target dimensions if they both check out
            if (
                (int) round($targetWidth * $height / $width) !== $targetHeight &&
                (int) round($targetHeight * $width / $height) !== $targetWidth
            ) {
                $factor = max($width / $targetWidth, $height / $targetHeight);
                $targetWidth = round($width / $factor);
                $targetHeight = round($height / $factor);
            }

            $this->resize($targetWidth, $targetHeight);
        }

        return $this;
    }

    public function scaleToFitAndFill(?int $targetWidth, ?int $targetHeight, ?string $fill = null, string|array $position = 'center-center', ?bool $upscale = null): static
    {
        $upscale ??= Cms::config()->upscaleImages;

        $this->normalizeDimensions($targetWidth, $targetHeight);
        $this->scaleToFit($targetWidth, $targetHeight, $upscale);
        $this->setFill($fill);

        $box = new Box($targetWidth, $targetHeight);
        $canvas = $this->_instance->create($box, $this->_fill);

        [$verticalPosition, $horizontalPosition] = explode('-', $position);

        $y = match ($verticalPosition) {
            'top' => 0,
            'bottom' => ($box->getHeight() - $this->getHeight()),
            default => ($box->getHeight() - $this->getHeight()) / 2,
        };

        $x = match ($horizontalPosition) {
            'left' => 0,
            'right' => ($box->getWidth() - $this->getWidth()),
            default => ($box->getWidth() - $this->getWidth()) / 2,
        };

        $point = new Point($x, $y);

        if ($this->_isAnimated) {
            $canvas->layers()->remove(0);
            $this->_image->layers()->coalesce();

            foreach ($this->_image->layers() as $layer) {
                $newLayer = $this->_instance->create($box, $this->_fill);
                $newLayer->paste($layer, $point);
                $canvas->layers()->add($newLayer);

                // Hopefully this doesn't take _too_ long, but it might
                $this->heartbeat();
            }
        } else {
            $canvas->paste($this->_image, $point);
        }

        $this->_image = $canvas;

        return $this;
    }

    public function scaleAndCrop(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true, array|string $cropPosition = 'center-center'): self
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);
        $width = $this->getWidth();
        $height = $this->getHeight();

        // If upscaling is fine OR we have to downscale.
        if ($scaleIfSmaller || ($width > $targetWidth && $height > $targetHeight)) {
            // Scale first.
            $factor = min($width / $targetWidth, $height / $targetHeight);
            $newHeight = (int) round($height / $factor);
            $newWidth = (int) round($width / $factor);

            $this->resize($newWidth, $newHeight);
            // If we need to upscale AND that's ok
        } elseif ($targetWidth > $width || $targetHeight > $height) {
            // Figure the crop size reductions
            $factor = max($targetWidth / $width, $targetHeight / $height);
            $newHeight = $height;
            $newWidth = $width;
            $targetHeight = round($targetHeight / $factor);
            $targetWidth = round($targetWidth / $factor);
        } else {
            $newHeight = $height;
            $newWidth = $width;
        }

        if (is_array($cropPosition)) {
            $centerX = $newWidth * $cropPosition['x'];
            $centerY = $newHeight * $cropPosition['y'];
            $x1 = $centerX - $targetWidth / 2;
            $y1 = $centerY - $targetHeight / 2;
            $x2 = $x1 + $targetWidth;
            $y2 = $y1 + $targetHeight;

            // Now see if we have to bump this around to make it fit the image.
            if ($x1 < 0) {
                $x2 -= $x1;
                $x1 = 0;
            }
            if ($y1 < 0) {
                $y2 -= $y1;
                $y1 = 0;
            }
            if ($x2 > $newWidth) {
                $x1 -= ($x2 - $newWidth);
                $x2 = $newWidth;
            }
            if ($y2 > $newHeight) {
                $y1 -= ($y2 - $newHeight);
                $y2 = $newHeight;
            }
        } else {
            [$verticalPosition, $horizontalPosition] = explode('-', $cropPosition);

            // Now crop.
            if ($newWidth - $targetWidth > 0) {
                switch ($horizontalPosition) {
                    case 'left':
                        $x1 = 0;
                        $x2 = $x1 + $targetWidth;
                        break;
                    case 'right':
                        $x2 = $newWidth;
                        $x1 = $newWidth - $targetWidth;
                        break;
                    default:
                        $x1 = round(($newWidth - $targetWidth) / 2);
                        $x2 = $x1 + $targetWidth;
                        break;
                }

                $y1 = 0;
                $y2 = $y1 + $targetHeight;
            } elseif ($newHeight - $targetHeight > 0) {
                switch ($verticalPosition) {
                    case 'top':
                        $y1 = 0;
                        $y2 = $y1 + $targetHeight;
                        break;
                    case 'bottom':
                        $y2 = $newHeight;
                        $y1 = $newHeight - $targetHeight;
                        break;
                    default:
                        $y1 = round(($newHeight - $targetHeight) / 2);
                        $y2 = $y1 + $targetHeight;
                        break;
                }

                $x1 = 0;
                $x2 = $x1 + $targetWidth;
            } else {
                $x1 = round(($newWidth - $targetWidth) / 2);
                $x2 = $x1 + $targetWidth;
                $y1 = round(($newHeight - $targetHeight) / 2);
                $y2 = $y1 + $targetHeight;
            }
        }

        $this->crop($x1, $x2, $y1, $y2);

        return $this;
    }

    public function resize(?int $targetWidth, ?int $targetHeight): self
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($this->_isAnimated) {
            // Create a new image instance to avoid object references messing up our dimensions.
            $newSize = new Box($targetWidth, $targetHeight);
            $gif = $this->_instance->create($newSize);
            $gif->layers()->remove(0);

            $this->_image->layers()->coalesce();
            foreach ($this->_image->layers() as $layer) {
                $resizedLayer = $layer->resize($newSize, $this->_getResizeFilter());
                $gif->layers()->add($resizedLayer);

                // Since it might take a while, send a heartbeat back
                $this->heartbeat();
            }

            $this->_image = $gif;
        } else {
            if ($this->_image instanceof ImagickImage && Cms::config()->optimizeImageFilesize) {
                $keepImageProfiles = Cms::config()->preserveImageColorProfiles;

                $this->_image->smartResize(new Box($targetWidth, $targetHeight), $keepImageProfiles, true, $this->_quality);
            } else {
                $this->_image->resize(new Box($targetWidth, $targetHeight), $this->_getResizeFilter());
            }

            if ($this->_image instanceof ImagickImage) {
                $this->_image->getImagick()->setImagePage(0, 0, 0, 0);
            }
        }

        return $this;
    }

    public function rotate(float $degrees): self
    {
        $this->_image->rotate((int) $degrees);

        if ($this->_image instanceof ImagickImage) {
            $this->_image->getImagick()->setImagePage($this->getWidth(), $this->getHeight(), 0, 0);
        }

        return $this;
    }

    public function flipHorizontally(): self
    {
        $this->_image->flipHorizontally();

        return $this;
    }

    public function flipVertically(): self
    {
        $this->_image->flipVertically();

        return $this;
    }

    public function setQuality(int $quality): self
    {
        $this->_quality = $quality;

        return $this;
    }

    public function setInterlace(string $interlace): self
    {
        $this->_image->interlace($interlace);

        return $this;
    }

    public function setFill(?string $fill = null): self
    {
        $fill ??= 'transparent';
        if ($fill === 'transparent') {
            $this->_fill = $this->_image->palette()->color('#ffffff', 0);
        } else {
            // set alpha to 100, otherwise it'll be set to 0 (fully transparent) for grayscale images
            $this->_fill = $this->_image->palette()->color($fill, 100);
        }

        return $this;
    }

    public function saveAs(string $targetPath, bool $autoQuality = false): bool
    {
        $extension = mb_strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        $targetPath = pathinfo($targetPath, PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.pathinfo($targetPath, PATHINFO_FILENAME).'.'.pathinfo($targetPath, PATHINFO_EXTENSION);
        $quality = null;

        try {
            if ($autoQuality && in_array($extension, ['jpeg', 'jpg', 'png'], true)) {
                clearstatcache();
                maxPowerCaptain();

                if (app(Images::class)->getIsImagick()) {
                    try {
                        $image = new Imagick($this->_imageSourcePath);
                        $quality = $image->getImageCompressionQuality();
                    } catch (ImagickException) {
                    }
                }

                if ($quality === null) {
                    $originalSize = filesize($this->_imageSourcePath);
                    $tempFile = $this->_autoGuessImageQuality($targetPath, $originalSize, $extension, 0, 200);
                    rename($tempFile, $targetPath);

                    return true;
                }
            }

            if ($this->_image instanceof ImagickImage) {
                ImageHelper::cleanExifDataFromImagickImage($this->_image->getImagick());
            }

            $options = $this->_getSaveOptions($quality, $extension);
            $this->_image->save($targetPath, $options);
        } catch (RuntimeException $e) {
            throw new ImageException(t('Failed to save the image.'), $e->getCode(), $e);
        }

        return true;
    }

    /**
     * Loads an image from an SVG string.
     *
     * @return self Self reference
     *
     * @throws ImageException if the SVG string cannot be loaded.
     */
    public function loadFromSVG(string $svgContent): self
    {
        try {
            $this->_image = $this->_instance->load($svgContent);
        } catch (RuntimeException) {
            try {
                // Invalid SVG. Maybe it's missing its DTD?
                $svgContent = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'.$svgContent;
                $this->_image = $this->_instance->load($svgContent);
            } catch (RuntimeException $e) {
                throw new ImageException(t('Failed to load the SVG string.'), $e->getCode(), $e);
            }
        }

        // PNG should be the best fit for SVGs.
        $this->_extension = 'png';

        return $this;
    }

    public function getIsTransparent(): bool
    {
        if ($this->_image instanceof ImagickImage) {
            // https://github.com/php-imagine/Imagine/issues/842#issuecomment-1402748019
            $alphaRange = $this->_image->getImagick()->getImageChannelRange(Imagick::CHANNEL_ALPHA);

            return
                isset($alphaRange['minima'], $alphaRange['maxima']) &&
                $alphaRange['minima'] < $alphaRange['maxima'];
        }

        return false;
    }

    /**
     * Returns EXIF metadata for a file by its path.
     */
    public function getExifMetadata(string $filePath): array
    {
        try {
            $exifReader = new ExifMetadataReader;
            $this->_instance->setMetadataReader($exifReader);
            $exif = $this->_instance->open($filePath)->metadata();

            return $exif->toArray();
        } catch (NotSupportedException $exception) {
            Log::error($exception->getMessage(), [__METHOD__]);

            return [];
        }
    }

    /**
     * Sets properties for text drawing on the image.
     *
     * @param  string  $fontFile  path to the font file on server
     * @param  int  $size  font size to use
     * @param  string  $color  font color to use in hex format
     */
    public function setFontProperties(string $fontFile, int $size, string $color): void
    {
        if (! isset($this->_palette)) {
            $this->_palette = new RGB;
        }

        /** @var AbstractFont $font */
        $font = $this->_instance->font($fontFile, $size, $this->_palette->color($color));
        $this->_font = $font;
    }

    /**
     * Returns the bounding text box for a text string and an angle
     *
     * @throws ImageException if attempting to create text box with no font properties
     */
    public function getTextBox(string $text, int $angle = 0): BoxInterface
    {
        if (! isset($this->_font)) {
            throw new ImageException(t('No font properties have been set. Call Raster::setFontProperties() first.'));
        }

        return $this->_font->box($text, $angle);
    }

    /**
     * Writes text on an image.
     *
     * @throws ImageException If attempting to create text box with no font properties et.
     */
    public function writeText(string $text, int $x, int $y, int $angle = 0): void
    {
        if (! isset($this->_font)) {
            throw new ImageException(t('No font properties have been set. Call ImageHelper::setFontProperties() first.'));
        }

        $point = new Point($x, $y);
        $this->_image->draw()->text($text, $this->_font, $point, $angle);
    }

    /**
     * Disable animation if this is an animated image.
     *
     * @return self Self-reference
     */
    public function disableAnimation(): self
    {
        $this->_isAnimated = false;

        if ($this->_image->layers()->count() > 1) {
            // Fetching the first layer returns the built-in Imagick object
            // So cycle that through the loading phase to get one that sports the
            // `smartResize` functionality.
            $this->_image = $this->_instance->load((string) $this->_image->layers()->get(0));
        }

        return $this;
    }

    /**
     * @return string the resulting file path
     */
    private function _autoGuessImageQuality(string $tempFileName, int $originalSize, string $extension, int $minQuality, int $maxQuality, int $step = 0): string
    {
        if ($step === 0) {
            $tempFileName = pathinfo($tempFileName, PATHINFO_DIRNAME).
                DIRECTORY_SEPARATOR.
                FileHelper::uniqueName(sprintf('%s.%s', pathinfo($tempFileName, PATHINFO_FILENAME), $extension));
        }

        // Find our target quality by splitting the min and max qualities
        $midQuality = (int) ceil($minQuality + (($maxQuality - $minQuality) / 2));

        // Set the min and max acceptable ranges. .10 means anything between 90% and 110% of the original file size is acceptable.
        $acceptableRange = .10;

        clearstatcache();

        // Generate a new temp image and get it's file size.
        $this->_image->save($tempFileName, $this->_getSaveOptions($midQuality, $extension));
        $newFileSize = filesize($tempFileName);

        // If we're on step 10 OR we're within our acceptable range threshold OR midQuality = maxQuality (1 == 1),
        // let's use the current image.
        if ($step == 10 || abs(1 - $originalSize / $newFileSize) < $acceptableRange || $midQuality == $maxQuality) {
            clearstatcache();

            // Generate one last time.
            if ($this->_image instanceof ImagickImage) {
                ImageHelper::cleanExifDataFromImagickImage($this->_image->getImagick());
            }

            $this->_image->save($tempFileName, $this->_getSaveOptions($midQuality));

            return $tempFileName;
        }

        $step++;

        if ($newFileSize > $originalSize) {
            return $this->_autoGuessImageQuality($tempFileName, $originalSize, $extension, $minQuality, $midQuality, $step);
        }

        // Too much.
        return $this->_autoGuessImageQuality($tempFileName, $originalSize, $extension, $midQuality, $maxQuality, $step);
    }

    private function _getResizeFilter(): string
    {
        return app(Images::class)->getIsGd() ? ImageInterface::FILTER_UNDEFINED : ImageInterface::FILTER_LANCZOS;
    }

    /**
     * Returns save options.
     */
    private function _getSaveOptions(?int $quality, ?string $extension = null): array
    {
        // Because it's possible for someone to set the quality to 0.
        $quality = $quality ?: $this->_quality;
        $extension = (! $extension ? mb_strtolower($this->getExtension()) : $extension);

        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                // ensure quality is between -1 and 100
                // https://github.com/craftcms/cms/issues/16977
                $quality = min(100, max(-1, $quality));

                return ['jpeg_quality' => $quality, 'flatten' => true];

            case 'gif':
                return ['animated' => $this->_isAnimated];

            case 'webp':
                return ['animated' => $this->_isAnimated, 'webp_quality' => $quality];

            case 'png':
                // Valid PNG quality settings are 0-9, so normalize and flip, because we're talking about compression
                // levels, not quality, like jpg and gif.
                $normalizedQuality = round(($quality * 9) / 100);
                $normalizedQuality = 9 - $normalizedQuality;
                if ($normalizedQuality < 0) {
                    $normalizedQuality = 0;
                }
                if ($normalizedQuality > 9) {
                    $normalizedQuality = 9;
                }
                $options = [
                    'png_compression_level' => $normalizedQuality,
                    'flatten' => false,
                ];

                if ($this->_imageSourcePath) {
                    $pngInfo = ImageHelper::pngImageInfo($this->_imageSourcePath);
                    // Even though a 2 channel PNG is valid (Grayscale with alpha channel), Imagick doesn't recognize it as
                    // a valid format: http://www.imagemagick.org/script/formats.php
                    // So 2 channel PNGs get converted to 4 channel.
                    if (is_array($pngInfo) && isset($pngInfo['channels']) && $pngInfo['channels'] !== 2) {
                        $format = 'png'.(8 * $pngInfo['channels']);
                    } else {
                        $format = 'png32';
                    }
                } else {
                    $format = 'png32';
                }

                $options['png_format'] = $format;

                return $options;

            default:
                return [
                    'quality' => $quality,
                ];
        }
    }
}
