<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use craft\helpers\FileHelper;
use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Images;
use Illuminate\Support\Facades\Log;
use Imagick;
use Imagine\Image\Format;
use InvalidArgumentException;
use Throwable;
use TypeError;

final class ImageHelper
{
    public static function calculateMissingDimension(float|int|null $targetWidth, float|int|null $targetHeight, float|int $sourceWidth, float|int $sourceHeight): array
    {
        if ($targetWidth && $targetHeight) {
            return [(int) $targetWidth, (int) $targetHeight];
        }

        if (! $sourceWidth || ! $sourceHeight) {
            throw new InvalidArgumentException('Image missing its width or height');
        }

        if (! $targetWidth && ! $targetHeight) {
            return [(int) $sourceWidth, (int) $sourceHeight];
        }

        return [
            (int) ($targetWidth ?: max(round($targetHeight * ($sourceWidth / $sourceHeight)), 1)),
            (int) ($targetHeight ?: max(round($targetWidth * ($sourceHeight / $sourceWidth)), 1)),
        ];
    }

    public static function targetDimensions(
        int $sourceWidth,
        int $sourceHeight,
        ?int $transformWidth,
        ?int $transformHeight,
        string $mode = 'crop',
        ?bool $upscale = null,
    ): array {
        [$width, $height] = self::calculateMissingDimension($transformWidth, $transformHeight, $sourceWidth, $sourceHeight);
        $factor = max($sourceWidth / $width, $sourceHeight / $height);

        $imageRatio = $sourceWidth / $sourceHeight;
        $transformRatio = $width / $height;

        if ($mode === 'letterbox') {
            return [$width, $height];
        }

        if ($upscale ?? Cms::config()->upscaleImages) {
            if ($mode === 'fit') {
                $width = (int) round($sourceWidth / $factor);
                $height = (int) round($sourceHeight / $factor);
            }

            return [$width, $height];
        }

        if ($mode === 'fit' || $imageRatio === $transformRatio) {
            $targetWidth = min($sourceWidth, $width, (int) round($sourceWidth / $factor));
            $targetHeight = min($sourceHeight, $height, (int) round($sourceHeight / $factor));

            return [$targetWidth, $targetHeight];
        }

        $newWidth = min($sourceWidth, $transformWidth ?? $width, (int) round($sourceHeight * $transformRatio));
        $newHeight = min($sourceHeight, $transformHeight ?? $height, (int) round($sourceWidth / $transformRatio));

        return [$newWidth, $newHeight];
    }

    public static function canManipulateAsImage(string $extension): bool
    {
        $extension = strtolower($extension);
        if ($extension === 'heif') {
            $extension = Format::ID_HEIC;
        }

        $formats = Images::getSupportedImageFormats();

        $alwaysManipulatable = ['svg'];
        $neverManipulatable = ['pdf', 'json', 'html', 'htm'];

        $formats = array_merge($formats, $alwaysManipulatable);
        $formats = array_diff($formats, $neverManipulatable);

        return in_array($extension, $formats);
    }

    public static function webSafeFormats(): array
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp', 'avif'];
    }

    public static function isWebSafe(string $extension): bool
    {
        return in_array(strtolower($extension), self::webSafeFormats(), true);
    }

    public static function pngImageInfo(string $file): array|false
    {
        if ($file === '') {
            return false;
        }

        $info = unpack(
            'A8sig/Nchunksize/A4chunktype/Nwidth/Nheight/Cbit-depth/Ccolor/Ccompression/Cfilter/Cinterface',
            file_get_contents($file, false, null, 0, 29)
        );

        if (empty($info)) {
            return false;
        }

        $sig = array_shift($info);

        if ($sig !== "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" && $sig !== "\x89\x50\x4E\x47\x0D\x0A\x1A") {
            return false;
        }

        if (array_shift($info) !== 13) {
            return false;
        }

        if (array_shift($info) !== 'IHDR') {
            return false;
        }

        $color = $info['color'];

        $type = [
            0 => 'Greyscale',
            2 => 'Truecolour',
            3 => 'Indexed-colour',
            4 => 'Greyscale with alpha',
            6 => 'Truecolor with alpha',
        ];

        if (empty($type[$color])) {
            return false;
        }

        $info['color-type'] = $type[$color];
        $samples = ((($color % 4) % 3) ? 3 : 1) + ($color > 3);
        $info['channels'] = $samples;

        return $info;
    }

    public static function canHaveExifData(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), ['jpg', 'jpeg', 'tiff'], true);
    }

    public static function cleanImageByPath(string $imagePath): void
    {
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);

        if (self::canManipulateAsImage($extension)) {
            Images::cleanImage($imagePath);
        }
    }

    public static function imageSize(string $filePath): array
    {
        try {
            if (FileHelper::isSvg($filePath)) {
                return self::parseSvgSize(file_get_contents($filePath));
            }

            $image = Images::loadImage($filePath);

            return [$image->getWidth(), $image->getHeight()];
        } catch (Throwable $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return [0, 0];
        }
    }

    public static function imageSizeByStream($stream): array|false
    {
        if (! is_resource($stream)) {
            throw new TypeError('Argument passed should be a resource.');
        }

        $dimensions = [];
        $signature = mb_strtoupper(bin2hex(stream_get_contents($stream, 2)));

        try {
            switch ($signature) {
                case 'FFD8':
                    $validFrames = [0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF];

                    while (true) {
                        $frameInfo = unpack('Cmarker/Ctype/nlength', stream_get_contents($stream, 4));

                        if ($frameInfo['marker'] !== 0xFF) {
                            throw new ImageException('Unrecognized JPG file structure.');
                        }

                        if (! $frameInfo['length']) {
                            break;
                        }

                        if (in_array($frameInfo['type'], $validFrames, true)) {
                            stream_get_contents($stream, 1);
                            $data = unpack('nheight/nwidth', stream_get_contents($stream, 4));
                            $dimensions = [$data['width'], $data['height']];
                            break;
                        }

                        stream_get_contents($stream, $frameInfo['length'] - 2);
                    }
                    break;
                case '4749':
                    $signature .= bin2hex(stream_get_contents($stream, 4));

                    if (! in_array($signature, ['474946383961', '474946383761'], true)) {
                        throw new ImageException('Unrecognized image signature.');
                    }

                    $data = unpack('v2', stream_get_contents($stream, 4));
                    $dimensions = array_values($data);
                    break;
                case '8950':
                    $signature .= mb_strtoupper(bin2hex(stream_get_contents($stream, 6)));

                    if ($signature !== '89504E470D0A1A0A') {
                        throw new ImageException('Unrecognized image signature.');
                    }

                    stream_get_contents($stream, 4);

                    $ihdr = bin2hex(stream_get_contents($stream, 4));
                    if ($ihdr !== '49484452') {
                        throw new ImageException('Unrecognized PNG file structure.');
                    }

                    $data = unpack('N2', stream_get_contents($stream, 8));
                    $dimensions = array_values($data);
                    break;
                default:
                    return false;
            }
        } catch (ImageException $exception) {
            Log::info($exception->getMessage(), [__METHOD__]);
        }

        return $dimensions;
    }

    public static function parseSvgSize(string $svg): array
    {
        if (
            preg_match(Svg::SVG_WIDTH_RE, $svg, $widthMatch) &&
            preg_match(Svg::SVG_HEIGHT_RE, $svg, $heightMatch) &&
            ($matchedWidth = (float) $widthMatch[2]) &&
            ($matchedHeight = (float) $heightMatch[2])
        ) {
            $width = (int) floor($matchedWidth * self::getSizeUnitMultiplier($widthMatch[3]));
            $height = (int) floor($matchedHeight * self::getSizeUnitMultiplier($heightMatch[3]));
        } elseif (preg_match(Svg::SVG_VIEWBOX_RE, $svg, $viewboxMatch)) {
            $width = (int) floor((float) $viewboxMatch[3]);
            $height = (int) floor((float) $viewboxMatch[4]);
        } else {
            $width = 100;
            $height = 100;
        }

        return [$width, $height];
    }

    public static function cleanExifDataFromImagickImage(Imagick $imagick): void
    {
        if (! Cms::config()->preserveExifData) {
            $iccProfiles = null;

            if (Cms::config()->preserveImageColorProfiles) {
                $iccProfiles = $imagick->getImageProfiles('icc', true);
            }

            $imagick->stripImage();

            if (! empty($iccProfiles)) {
                $imagick->profileImage('icc', $iccProfiles['icc'] ?? '');
            }
        }
    }

    private static function getSizeUnitMultiplier(string $unit): float
    {
        $ppi = 72;

        return match ($unit) {
            'in' => $ppi,
            'pt' => $ppi / 72,
            'pc' => $ppi / 6,
            'cm' => $ppi / 2.54,
            'mm' => $ppi / 25.4,
            'em' => 16,
            'ex' => 10,
            default => 1,
        };
    }
}
