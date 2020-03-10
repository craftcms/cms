<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\ImageException;
use craft\image\Svg;
use yii\base\InvalidArgumentException;

/**
 * Class Image
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Image
{
    const EXIF_IFD0_ROTATE_180 = 3;
    const EXIF_IFD0_ROTATE_90 = 6;
    const EXIF_IFD0_ROTATE_270 = 8;

    /**
     * Calculates a missing target dimension for an image.
     *
     * @param int|float|null $targetWidth
     * @param int|float|null $targetHeight
     * @param int|float $sourceWidth
     * @param int|float $sourceHeight
     * @return int[] Array of the width and height.
     */
    public static function calculateMissingDimension($targetWidth, $targetHeight, $sourceWidth, $sourceHeight): array
    {
        // If the target width & height are both present, return them
        if ($targetWidth && $targetHeight) {
            return [(int)$targetWidth, (int)$targetHeight];
        }

        // Make sure that there's a source width/height
        if (!$sourceWidth || !$sourceHeight) {
            throw new InvalidArgumentException('Image missing its width or height');
        }

        // If neither were supplied, just use the source dimensions
        if (!$targetWidth && !$targetHeight) {
            return [(int)$sourceWidth, (int)$sourceHeight];
        }

        // Fill in the blank
        return [
            (int)($targetWidth ?: ceil($targetHeight * ($sourceWidth / $sourceHeight))),
            (int)($targetHeight ?: ceil($targetWidth * ($sourceHeight / $sourceWidth))),
        ];
    }

    /**
     * Returns whether an image extension is considered manipulatable.
     *
     * @param string $extension
     * @return bool
     */
    public static function canManipulateAsImage(string $extension): bool
    {
        $formats = Craft::$app->getImages()->getSupportedImageFormats();

        $alwaysManipulatable = ['svg'];
        $neverManipulatable = ['pdf', 'json', 'html', 'htm'];

        $formats = array_merge($formats, $alwaysManipulatable);
        $formats = array_diff($formats, $neverManipulatable);

        return in_array(strtolower($extension), $formats);
    }

    /**
     * Returns a list of web safe image formats.
     *
     * @return string[]
     */
    public static function webSafeFormats(): array
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'];
    }

    /**
     * Returns any info that’s embedded in a given PNG file.
     *
     * Adapted from https://github.com/ktomk/Miscellaneous/tree/master/get_png_imageinfo.
     *
     * @param string $file The path to the PNG file.
     * @return array|bool Info embedded in the PNG file, or `false` if it wasn’t found.
     * @license Apache 2.0
     * @version 0.1.0
     * @link http://www.libpng.org/pub/png/spec/iso/index-object.html#11IHDR
     * @author Tom Klingenberg <lastflood.net>
     */
    public static function pngImageInfo(string $file)
    {
        if (empty($file)) {
            return false;
        }

        $info = unpack(
            'A8sig/Nchunksize/A4chunktype/Nwidth/Nheight/Cbit-depth/Ccolor/Ccompression/Cfilter/Cinterface',
            file_get_contents($file, 0, null, 0, 29)
        );

        if (empty($info)) {
            return false;
        }

        $sig = array_shift($info);

        if ($sig != "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" && $sig != "\x89\x50\x4E\x47\x0D\x0A\x1A") {
            // The file doesn't have a PNG signature
            return false;
        }

        if (array_shift($info) != 13) {
            // The IHDR chunk has the wrong length
            return false;
        }

        if (array_shift($info) !== 'IHDR') {
            // A non-IHDR chunk singals invalid data
            return false;
        }

        $color = $info['color'];

        $type = [
            0 => 'Greyscale',
            2 => 'Truecolour',
            3 => 'Indexed-colour',
            4 => 'Greyscale with alpha',
            6 => 'Truecolor with alpha'
        ];

        if (empty($type[$color])) {
            // Invalid color value
            return false;
        }

        $info['color-type'] = $type[$color];
        $samples = ((($color % 4) % 3) ? 3 : 1) + ($color > 3);
        $info['channels'] = $samples;

        return $info;
    }

    /**
     * Returns whether an image can have EXIF information embedded.
     *
     * @param string $filePath the file path to check.
     * @return bool
     */
    public static function canHaveExifData(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), ['jpg', 'jpeg', 'tiff'], true);
    }

    /**
     * Clean an image provided by path from all malicious code and the like.
     *
     * @param string $imagePath
     */
    public static function cleanImageByPath(string $imagePath)
    {
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);

        if (static::canManipulateAsImage($extension)) {
            Craft::$app->getImages()->cleanImage($imagePath);
        }
    }

    /**
     * Returns the size of an image based on its file path.
     *
     * @param string $filePath The path to the image
     * @return array [width, height]
     */
    public static function imageSize(string $filePath): array
    {
        try {
            if (FileHelper::isSvg($filePath)) {
                $svg = file_get_contents($filePath);
                return static::parseSvgSize($svg);
            }

            $image = Craft::$app->getImages()->loadImage($filePath);
            return [$image->getWidth(), $image->getHeight()];
        } catch (\Throwable $exception) {
            return [0, 0];
        }
    }

    /**
     * Determines image dimensions by a stream pointing to the start of the image.
     *
     * @param resource $stream
     * @return array|false
     * @throws \TypeError
     */
    public static function imageSizeByStream($stream)
    {
        if (!is_resource($stream)) {
            throw new \TypeError('Argument passed should be a resource.');
        }

        $dimensions = [];

        // PNG 8 byte signature 0x89 0x50 0x4E 0x47 0x0D 0x0A 0x1A 0x0A
        // GIF 6 byte signature 0x47 0x49 0x46 0x38 0x39|0x37 0x61
        // JPG 2 byte signature 0xFF 0xD8

        // It's much easier to work with a HEX string here, because of variable signature lengths
        $signature = mb_strtoupper(bin2hex(stream_get_contents($stream, 2)));

        try {
            switch ($signature) {
                // Must be JPG
                case 'FFD8':
                    // List of JPEG frame types we know how to extract size from
                    $validFrames = [0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF];

                    while (true) {
                        // Read JPEG frame info.
                        $frameInfo = unpack('Cmarker/Ctype/nlength', stream_get_contents($stream, 4));

                        if ($frameInfo['marker'] !== 0xFF) {
                            throw new ImageException('Unrecognized JPG file structure.');
                        }

                        // Ran out of file so something must be wrong.
                        if (!$frameInfo['length']) {
                            break;
                        }

                        if (in_array($frameInfo['type'], $validFrames, true)) {
                            // Dud.
                            stream_get_contents($stream, 1);

                            // Load dimensions
                            $data = unpack('nheight/nwidth', stream_get_contents($stream, 4));
                            $dimensions = [$data['width'], $data['height']];
                            break;
                        }

                        // Dud.
                        stream_get_contents($stream, $frameInfo['length'] - 2);
                    }
                    break;
                // Probably GIF
                case '4749':
                    $signature .= bin2hex(stream_get_contents($stream, 4));

                    // Make sure it's GIF
                    if (!in_array($signature, ['474946383961', '474946383761'], true)) {
                        throw new ImageException('Unrecognized image signature.');
                    }

                    // Unpack next 4 bytes as two unsigned integers with little endian byte order and call it a day
                    $data = unpack('v2', stream_get_contents($stream, 4));
                    $dimensions = array_values($data);
                    break;
                // Maybe PNG
                case '8950':
                    $signature .= mb_strtoupper(bin2hex(stream_get_contents($stream, 6)));

                    // Make sure it's PNG
                    if ($signature !== '89504E470D0A1A0A') {
                        throw new ImageException('Unrecognized image signature.');
                    }

                    // Dud.
                    stream_get_contents($stream, 4);

                    // IHDR chunk MUST be first
                    $ihdr = bin2hex(stream_get_contents($stream, 4));
                    if ($ihdr !== '49484452') {
                        throw new ImageException('Unrecognized PNG file structure.');
                    }

                    // Unpack next 8 bytes as two unsigned long integers with big endian byte order and call it a day
                    $data = unpack('N2', stream_get_contents($stream, 8));
                    $dimensions = array_values($data);

                    break;
                default:
                    return false;
            }
        } catch (ImageException $exception) {
            Craft::info($exception->getMessage(), __METHOD__);
        }

        return $dimensions;
    }

    /**
     * Parses SVG data and determines its size (normalized to pixels).
     *
     * @param string $svg The SVG data
     * @return array [width, height]
     */
    public static function parseSvgSize(string $svg): array
    {
        if (
            preg_match(Svg::SVG_WIDTH_RE, $svg, $widthMatch) &&
            preg_match(Svg::SVG_HEIGHT_RE, $svg, $heightMatch) &&
            ($matchedWidth = (float)$widthMatch[2]) &&
            ($matchedHeight = (float)$heightMatch[2])
        ) {
            $width = floor(
                $matchedWidth * self::_getSizeUnitMultiplier($widthMatch[3])
            );
            $height = floor(
                $matchedHeight * self::_getSizeUnitMultiplier($heightMatch[3])
            );
        } else if (preg_match(Svg::SVG_VIEWBOX_RE, $svg, $viewboxMatch)) {
            $width = floor($viewboxMatch[3]);
            $height = floor($viewboxMatch[4]);
        } else {
            // Just pretend it's 100x100
            $width = 100;
            $height = 100;
        }

        return [$width, $height];
    }

    /**
     * Clean EXIF data from an image loaded inside an Imagick instance, taking
     * care not to wipe the ICC profile.
     *
     * @param \Imagick $imagick
     */
    public static function cleanExifDataFromImagickImage(\Imagick $imagick)
    {
        $config = Craft::$app->getConfig()->getGeneral();

        if (!$config->preserveExifData) {
            $iccProfiles = null;
            $supportsImageProfiles = method_exists($imagick, 'getimageprofiles');

            if ($config->preserveImageColorProfiles && $supportsImageProfiles) {
                $iccProfiles = $imagick->getImageProfiles("icc", true);
            }

            $imagick->stripImage();

            if (!empty($iccProfiles)) {
                $imagick->profileImage("icc", $iccProfiles['icc'] ?? '');
            }
        }
    }

    /**
     * Returns the multiplier that should be used to convert an image size unit to pixels.
     *
     * @param string $unit
     * @return float The multiplier
     */
    private static function _getSizeUnitMultiplier(string $unit): float
    {
        $ppi = 72;

        switch ($unit) {
            case 'in':
                return $ppi;
            case 'pt':
                return $ppi / 72;
            case 'pc':
                return $ppi / 6;
            case 'cm':
                return $ppi / 2.54;
            case 'mm':
                return $ppi / 25.4;
            case 'em':
                return 16;
            case 'ex':
                return 10;
            default:
                return 1;
        }
    }
}
