<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\File;
use GdImage;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickException;
use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Direction;
use Intervention\Image\Drivers\Imagick\FontProcessor as ImagickFontProcessor;
use Intervention\Image\Drivers\Vips\Core as VipsCore;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\BmpEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\HeicEncoder;
use Intervention\Image\Encoders\IcoEncoder;
use Intervention\Image\Encoders\Jpeg2000Encoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\JxlEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\TiffEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageException as InterventionImageException;
use Intervention\Image\Interfaces\EncoderInterface;
use Intervention\Image\Interfaces\FontInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;
use Jcupitt\Vips\Exception as VipsException;
use Jcupitt\Vips\Image as VipsImage;
use Jcupitt\Vips\Interpretation;
use Throwable;

use function CraftCms\Cms\maxPowerCaptain;
use function CraftCms\Cms\t;

class Raster extends Image
{
    private ?string $_imageSourcePath = null;

    private ?string $_extension = null;

    private int $_quality;

    private string $_interlace = 'none';

    private ?ImageInterface $_image = null;

    private ?FontInterface $_font = null;

    private string $_fill = 'ffffff00';

    public function __construct($config = [])
    {
        $generalConfig = Cms::config();

        $this->_quality = $generalConfig->defaultImageQuality;

        parent::__construct($config);
    }

    public function getInterventionImage(): ?ImageInterface
    {
        return $this->_image;
    }

    public function getWidth(): int
    {
        return $this->_image->width();
    }

    public function getHeight(): int
    {
        return $this->_image->height();
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
        $mimeType = File::getMimeType($path, false);

        if ($mimeType !== null && ! str_starts_with($mimeType, 'image/') && ! str_starts_with($mimeType, 'application/pdf')) {
            throw new ImageException(t('The file “{name}” does not appear to be an image.', [
                'name' => basename($path),
            ]));
        }

        try {
            $this->_image = $imageService->getManager()->decodePath($path);
        } catch (Throwable $e) {
            // Image drivers can throw all sorts of errors while opening files,
            // so log them to provide more context.
            Log::info($e->getMessage(), ['file' => $e->getFile()]);
            throw new ImageException(t('The file “{name}” does not appear to be an image.', [
                'name' => basename($path),
            ]), 0, $e);
        }

        // Convert CMYK images to RGB.
        if (
            ! Cms::config()->preserveCmykColorspace
            && $this->_image->colorspace() instanceof CmykColorspace
        ) {
            $native = $this->_image->core()->native();

            try {
                if ($native instanceof VipsImage) {
                    $this->_image->core()->setNative($native->icc_transform(Interpretation::SRGB));
                } else {
                    $this->_image->setColorspace(RgbColorspace::class);
                }
            } catch (InterventionImageException|VipsException $e) {
                throw new ImageException(t('Failed to convert the image to the sRGB color space.'), $e->getCode(), $e);
            }
        }

        $this->_imageSourcePath = $path;
        $this->_extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this;
    }

    public function crop(int $x1, int $x2, int $y1, int $y2): self
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        $this->_image->crop($width, $height, $x1, $y1);

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
                (int) round($targetWidth * $height / $width) !== $targetHeight
                && (int) round($targetHeight * $width / $height) !== $targetWidth
            ) {
                $factor = max($width / $targetWidth, $height / $targetHeight);
                $targetWidth = (int) round($width / $factor);
                $targetHeight = (int) round($height / $factor);
            }

            $this->resize($targetWidth, $targetHeight);
        }

        return $this;
    }

    /** @param array{x:numeric,y:numeric}|string $position */
    public function scaleToFitAndFill(?int $targetWidth, ?int $targetHeight, ?string $fill = null, string|array $position = 'center-center', ?bool $upscale = null): static
    {
        $upscale ??= Cms::config()->upscaleImages;

        $this->normalizeDimensions($targetWidth, $targetHeight);
        $this->scaleToFit($targetWidth, $targetHeight, $upscale);
        $this->setFill($fill);
        $this->_image->resizeCanvas($targetWidth, $targetHeight, $this->_fill, $position);

        return $this;
    }

    /** @param array{x:numeric,y:numeric}|string $cropPosition */
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
            $targetHeight = (int) round($targetHeight / $factor);
            $targetWidth = (int) round($targetWidth / $factor);
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
                $x1 = match ($horizontalPosition) {
                    'left' => 0,
                    'right' => $newWidth - $targetWidth,
                    default => round(($newWidth - $targetWidth) / 2),
                };
                $x2 = $x1 + $targetWidth;
                $y1 = 0;
                $y2 = $y1 + $targetHeight;
            } elseif ($newHeight - $targetHeight > 0) {
                $y1 = match ($verticalPosition) {
                    'top' => 0,
                    'bottom' => $newHeight - $targetHeight,
                    default => round(($newHeight - $targetHeight) / 2),
                };
                $y2 = $y1 + $targetHeight;
                $x1 = 0;
                $x2 = $x1 + $targetWidth;
            } else {
                $x1 = round(($newWidth - $targetWidth) / 2);
                $x2 = $x1 + $targetWidth;
                $y1 = round(($newHeight - $targetHeight) / 2);
                $y2 = $y1 + $targetHeight;
            }
        }

        $this->crop((int) $x1, (int) $x2, (int) $y1, (int) $y2);

        return $this;
    }

    public function resize(?int $targetWidth, ?int $targetHeight): self
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);
        $this->_image->resize($targetWidth, $targetHeight);

        return $this;
    }

    public function rotate(float $degrees): self
    {
        $this->_image->rotate((int) $degrees);

        return $this;
    }

    public function flipHorizontally(): self
    {
        $this->_image->flip(Direction::HORIZONTAL);

        return $this;
    }

    public function flipVertically(): self
    {
        $this->_image->flip(Direction::VERTICAL);

        return $this;
    }

    public function setQuality(int $quality): self
    {
        $this->_quality = $quality;

        return $this;
    }

    public function setInterlace(string $interlace): self
    {
        $this->_interlace = $interlace;

        return $this;
    }

    public function setFill(?string $fill = null): self
    {
        $this->_fill = $fill ?? 'ffffff00';

        return $this;
    }

    public function saveAs(string $targetPath, bool $autoQuality = false): bool
    {
        $extension = mb_strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        try {
            $this->prepareMetadata();

            if ($autoQuality && in_array($extension, ['jpeg', 'jpg', 'png'], true)) {
                $quality = $this->sourceQuality();

                if ($quality === null) {
                    $originalSize = filesize($this->_imageSourcePath);
                    $tempFile = $this->autoGuessImageQuality($targetPath, $originalSize, $extension);
                    rename($tempFile, $targetPath);

                    return true;
                }

                $this->encodeToPath($targetPath, $extension, $quality);

                return true;
            }

            $this->encodeToPath($targetPath, $extension, $this->_quality);
        } catch (ImageException $e) {
            throw $e;
        } catch (Throwable $e) {
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
            $this->_image = app(Images::class)->getManager()->decodeBinary($svgContent);
        } catch (InterventionImageException) {
            try {
                // Invalid SVG. Maybe it's missing its DTD?
                $svgContent = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'.$svgContent;
                $this->_image = app(Images::class)->getManager()->decodeBinary($svgContent);
            } catch (InterventionImageException $e) {
                throw new ImageException(t('Failed to load the SVG string.'), $e->getCode(), $e);
            }
        }

        // PNG should be the best fit for SVGs.
        $this->_extension = 'png';

        return $this;
    }

    public function getIsTransparent(): bool
    {
        foreach ($this->_image as $frame) {
            $native = $frame->native();

            if ($native instanceof Imagick) {
                if (! $native->getImageAlphaChannel()) {
                    continue;
                }

                $range = $native->getImageChannelRange(Imagick::CHANNEL_ALPHA);
                $quantum = Imagick::getQuantumRange()['quantumRangeLong'];
                if (($range['minima'] ?? $quantum) < $quantum) {
                    return true;
                }

                continue;
            }

            if ($native instanceof GdImage && $this->gdImageIsTransparent($native)) {
                return true;
            }

            if ($native instanceof VipsImage && $native->hasAlpha()) {
                $opaque = match ($native->format) {
                    'ushort' => 65535,
                    'float', 'double' => 1,
                    default => 255,
                };

                if ($native->extract_band($native->bands - 1)->min() < $opaque) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns EXIF metadata for a file by its path.
     *
     * @return array<string,mixed>
     */
    public function getExifMetadata(string $filePath): array
    {
        if (! function_exists('exif_read_data')) {
            return [];
        }

        try {
            $metadata = app(Images::class)->getManager()->decodePath($filePath)->exif()->toArray();
        } catch (InterventionImageException $e) {
            Log::error($e->getMessage(), [__METHOD__]);

            return [];
        }

        $result = [];
        foreach ($metadata as $section => $values) {
            if (! is_array($values)) {
                $result[$section] = $values;

                continue;
            }

            foreach ($values as $key => $value) {
                $result[mb_strtolower((string) $section).'.'.$key] = $value;
            }
        }

        return $result;
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
        $this->_font = FontFactory::build(
            fn (FontFactory $font) => $font
                ->filepath($fontFile)
                ->size($size)
                ->color($color),
        );
    }

    /**
     * Returns the bounding text box for a text string and an angle
     *
     * @return array{width: int, height: int}
     *
     * @throws ImageException if attempting to create text box with no font properties
     */
    public function getTextBox(string $text, int $angle = 0): array
    {
        if (! isset($this->_font)) {
            throw new ImageException(t('No font properties have been set. Call Raster::setFontProperties() first.'));
        }

        $font = clone $this->_font;
        $font->setAngle($angle);
        $fontProcessor = $this->_image->driver()->fontProcessor();
        $size = $fontProcessor->boxSize($text, $font);
        $native = $this->_image->core()->native();

        if ($native instanceof Imagick && $fontProcessor instanceof ImagickFontProcessor && $text !== '') {
            $metrics = $native->queryFontMetrics($fontProcessor->toImagickDraw($font), $text);
            $size = [
                'width' => (int) round($metrics['textWidth']),
                'height' => (int) round($metrics['ascender'] - $metrics['descender']),
            ];
        } else {
            $size = ['width' => $size->width(), 'height' => $size->height()];
        }

        $radians = deg2rad($angle);
        $width = (int) ceil(round(abs($size['width'] * cos($radians)) + abs($size['height'] * sin($radians)), 10));
        $height = (int) ceil(round(abs($size['width'] * sin($radians)) + abs($size['height'] * cos($radians)), 10));

        return ['width' => $width, 'height' => $height];
    }

    /**
     * Writes text on an image.
     *
     * @throws ImageException If attempting to create text box with no font properties et.
     */
    public function writeText(string $text, int $x, int $y, int $angle = 0): void
    {
        if (! isset($this->_font)) {
            throw new ImageException(t('No font properties have been set. Call Raster::setFontProperties() first.'));
        }

        $font = clone $this->_font;
        $font->setAngle($angle);
        $this->_image->text($text, $x, $y, $font);
    }

    /**
     * Disable animation if this is an animated image.
     *
     * @return self Self-reference
     */
    public function disableAnimation(): self
    {
        if ($this->_image->isAnimated()) {
            $this->_image->removeAnimation(0);
        }

        return $this;
    }

    public function orient(): self
    {
        $this->_image->orient();

        return $this;
    }

    private function sourceQuality(): ?int
    {
        if (! app(Images::class)->getIsImagick() || $this->_imageSourcePath === null) {
            return null;
        }

        try {
            $image = new Imagick($this->_imageSourcePath);

            return $image->getImageCompressionQuality();
        } catch (ImagickException) {
            return null;
        }
    }

    /**
     * @return string the resulting file path
     */
    private function autoGuessImageQuality(string $targetPath, int $originalSize, string $extension): string
    {
        maxPowerCaptain();

        if ($this->_image->core() instanceof VipsCore) {
            VipsCore::ensureInMemory($this->_image->core());
        }

        $tempPath = pathinfo($targetPath, PATHINFO_DIRNAME).DIRECTORY_SEPARATOR.File::uniqueName(
            pathinfo($targetPath, PATHINFO_FILENAME).'.'.$extension,
        );
        $minimum = 0;
        $maximum = 100;

        // Set the min and max acceptable ranges. .10 means anything between 90% and 110% of the original file size is acceptable.
        $acceptableRange = .10;

        for ($step = 0; $step < 10; $step++) {
            // Find our target quality by splitting the min and max qualities
            $quality = (int) ceil($minimum + (($maximum - $minimum) / 2));

            // Generate a new temp image and get it's file size.
            $this->encodeToPath($tempPath, $extension, $quality);
            clearstatcache(true, $tempPath);
            $newSize = filesize($tempPath);

            if (abs(1 - $originalSize / $newSize) < $acceptableRange || $minimum === $maximum) {
                return $tempPath;
            }

            if ($newSize > $originalSize) {
                $maximum = max($minimum, $quality - 1);
            } else {
                $minimum = min($maximum, $quality + 1);
            }
        }

        return $tempPath;
    }

    private function encodeToPath(string $targetPath, string $extension, int $quality): void
    {
        // Ensure quality is between 0 and 100.
        // https://github.com/craftcms/cms/issues/16977
        $quality = max(0, min(100, $quality));
        $this->applyInterlace();

        if ($extension === 'png') {
            $this->encodePng($targetPath, $quality);

            return;
        }

        $this->_image->encode($this->encoder($extension, $quality))->save($targetPath);
    }

    private function encoder(string $extension, int $quality): EncoderInterface
    {
        $strip = ! Cms::config()->preserveExifData;
        $interlaced = $this->_interlace !== 'none' && ! app(Images::class)->getIsImagick();

        return match ($extension) {
            'jpg', 'jpeg', 'pjpg', 'pjpeg' => new JpegEncoder($quality, $interlaced, $strip),
            'gif' => new GifEncoder($interlaced),
            'png' => new PngEncoder($interlaced),
            'webp' => new WebpEncoder(Cms::config()->optimizeImageFilesize ? $quality : 100, $strip),
            'avif' => new AvifEncoder($quality, $strip),
            'bmp' => new BmpEncoder,
            'heic', 'heif' => new HeicEncoder($quality, $strip),
            'ico' => new IcoEncoder,
            'jp2', 'j2k', 'jp2k', 'jpf', 'jpm', 'jpg2', 'j2c', 'jpc', 'jpx' => new Jpeg2000Encoder($quality, $strip),
            'jxl' => new JxlEncoder($quality, $strip),
            'tif', 'tiff' => new TiffEncoder($quality, $strip),
            default => throw new ImageException(t('The image format “{format}” is not supported.', ['format' => $extension])),
        };
    }

    private function applyInterlace(): void
    {
        $native = $this->_image->core()->native();
        if (! $native instanceof Imagick) {
            return;
        }

        $native->setInterlaceScheme(match ($this->_interlace) {
            'line' => Imagick::INTERLACE_LINE,
            'plane' => Imagick::INTERLACE_PLANE,
            'partition' => Imagick::INTERLACE_PARTITION,
            default => Imagick::INTERLACE_NO,
        });
    }

    private function encodePng(string $targetPath, int $quality): void
    {
        $native = $this->_image->core()->native();

        // Valid PNG quality settings are 0-9, so normalize and flip, because we're talking about compression
        // levels, not quality, like jpg and gif.
        $compression = max(0, min(9, 9 - (int) round(($quality * 9) / 100)));

        if ($native instanceof Imagick) {
            $this->encodeImagickPng($targetPath, $compression);

            return;
        }

        if ($native instanceof GdImage) {
            imageinterlace($native, $this->_interlace !== 'none');
            if (! imagepng($native, $targetPath, $compression)) {
                throw new ImageException(t('Failed to save the image.'));
            }

            return;
        }

        if ($native instanceof VipsImage) {
            if ($this->_image->isAnimated()) {
                $native = $this->_image->core()->frame(0)->native();
            }

            $result = $native->writeToBuffer('.png', [
                'compression' => $compression,
                'interlace' => $this->_interlace !== 'none',
            ]);

            if (file_put_contents($targetPath, $result) === false) {
                throw new ImageException(t('Failed to save the image.'));
            }

            return;
        }

        $this->_image->encode(new PngEncoder($this->_interlace !== 'none'))->save($targetPath);
    }

    private function encodeImagickPng(string $targetPath, int $compression): void
    {
        $output = clone $this->_image->core()->native();
        $output->setOption('png:compression-level', (string) $compression);

        $pngInfo = $this->_imageSourcePath ? ImageHelper::pngImageInfo($this->_imageSourcePath) : false;
        $channels = is_array($pngInfo) ? $pngInfo['channels'] ?? 4 : 4;

        // Even though a 2 channel PNG is valid (Grayscale with alpha channel), Imagick doesn't recognize it as
        // a valid format: http://www.imagemagick.org/script/formats.php
        // So 2 channel PNGs get converted to 4 channel.
        $format = $channels === 2 ? 'PNG32' : 'PNG'.(8 * $channels);

        if ($format === 'PNG8') {
            $output->quantizeImage(255, Imagick::COLORSPACE_YUV, 8, false, false);
            $output->setImageFormat('PNG');
        } else {
            $output->setImageFormat($format);
        }

        $output->writeImages($targetPath, true);
        $output->clear();
    }

    private function prepareMetadata(): void
    {
        $native = $this->_image->core()->native();
        if ($native instanceof VipsImage) {
            if (! Cms::config()->preserveExifData) {
                foreach (['exif-data', 'xmp-data', 'iptc-data', 'orientation'] as $field) {
                    if ($native->getType($field) !== 0) {
                        $native->remove($field);
                    }
                }
            }

            if (! Cms::config()->preserveImageColorProfiles && $native->getType('icc-profile-data') !== 0) {
                $native->remove('icc-profile-data');
            }

            return;
        }

        if (! $native instanceof Imagick) {
            if (! Cms::config()->preserveImageColorProfiles) {
                try {
                    $this->_image->removeProfile();
                } catch (InterventionImageException) {
                }
            }

            return;
        }

        if (Cms::config()->preserveExifData) {
            if (! Cms::config()->preserveImageColorProfiles) {
                try {
                    $native->removeImageProfile('icc');
                } catch (ImagickException) {
                }
            }

            return;
        }

        try {
            $profile = Cms::config()->preserveImageColorProfiles
                ? $native->getImageProfile('icc')
                : '';
        } catch (ImagickException) {
            $profile = '';
        }
        $native->stripImage();

        if ($profile !== '') {
            $native->profileImage('icc', $profile);
        }
    }

    private function gdImageIsTransparent(GdImage $image): bool
    {
        $transparent = imagecolortransparent($image);
        if ($transparent >= 0) {
            return true;
        }

        for ($y = 0, $height = imagesy($image); $y < $height; $y++) {
            for ($x = 0, $width = imagesx($image); $x < $width; $x++) {
                if ((imagecolorat($image, $x, $y) & 0x7F000000) !== 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
