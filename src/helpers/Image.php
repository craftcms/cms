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
use Imagick;
use Imagine\Image\Format;
use Throwable;
use TypeError;
use yii\base\InvalidArgumentException;

/**
 * Class Image
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Image
{
    // Bounds metadata scans for formats whose dimensions aren't in fixed header bytes.
    private const MAX_IMAGE_SIZE_STREAM_BYTES = 1024 * 1024;

    /** @since 5.6.0 */
    public const EXIF_IFD0_ROTATE_0 = 1;
    /** @since 5.6.0 */
    public const EXIF_IFD0_ROTATE_0_MIRRORED = 2;
    public const EXIF_IFD0_ROTATE_180 = 3;
    /** @since 5.6.0 */
    public const EXIF_IFD0_ROTATE_180_MIRRORED = 4;
    /** @since 5.6.0 */
    public const EXIF_IFD0_ROTATE_90_MIRRORED = 5;
    public const EXIF_IFD0_ROTATE_90 = 6;
    /** @since 5.6.0 */
    public const EXIF_IFD0_ROTATE_270_MIRRORED = 7;
    public const EXIF_IFD0_ROTATE_270 = 8;

    /**
     * Calculates a missing target dimension for an image.
     *
     * @param float|int|null $targetWidth
     * @param float|int|null $targetHeight
     * @param float|int $sourceWidth
     * @param float|int $sourceHeight
     * @return int[] Array of the width and height.
     */
    public static function calculateMissingDimension(float|int|null $targetWidth, float|int|null $targetHeight, float|int $sourceWidth, float|int $sourceHeight): array
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

        // Fill in the blank,
        // ensure that the target width/height is at least 1
        return [
            (int)($targetWidth ?: max(round($targetHeight * ($sourceWidth / $sourceHeight)), 1)),
            (int)($targetHeight ?: max(round($targetWidth * ($sourceHeight / $sourceWidth)), 1)),
        ];
    }

    /**
     * Returns the target image width and height for an image, based on its transform type and constraints,
     * and whether the source image should be upscaled.
     *
     * @param int $sourceWidth
     * @param int $sourceHeight
     * @param int|null $transformWidth
     * @param int|null $transformHeight
     * @param string $mode The transform mode (`crop`, `fit`, `letterbox` or `stretch`)
     * @param bool|null $upscale Whether to upscale the image to fill the transform dimensions.
     * Defaults to the `upscaleImages` config setting.
     * @return int[]
     * @phpstan-return array{int,int}
     * @since 3.7.55
     */
    public static function targetDimensions(
        int $sourceWidth,
        int $sourceHeight,
        ?int $transformWidth,
        ?int $transformHeight,
        string $mode = 'crop',
        ?bool $upscale = null,
    ): array {
        [$width, $height] = static::calculateMissingDimension($transformWidth, $transformHeight, $sourceWidth, $sourceHeight);
        $factor = max($sourceWidth / $width, $sourceHeight / $height);

        $imageRatio = $sourceWidth / $sourceHeight;
        $transformRatio = $width / $height;

        // When mode is `letterbox` always use the transform size
        if ($mode === 'letterbox') {
            return [$width, $height];
        }

        if ($upscale ?? Craft::$app->getConfig()->getGeneral()->upscaleImages) {
            // Special case for 'fit' since that's the only one whose dimensions vary from the transform dimensions
            if ($mode === 'fit') {
                $width = (int)round($sourceWidth / $factor);
                $height = (int)round($sourceHeight / $factor);
            }

            return [$width, $height];
        }

        // When mode is `fit` or the source is the same ratio as the transform
        if ($mode === 'fit' || $imageRatio === $transformRatio) {
            $targetWidth = min($sourceWidth, $width, (int)round($sourceWidth / $factor));
            $targetHeight = min($sourceHeight, $height, (int)round($sourceHeight / $factor));
            return [$targetWidth, $targetHeight];
        }

        // Since we don't want to upscale, make sure the calculated ratios aren't bigger than the actual image size.
        // transformWidth and transformHeight can be null, so check for that and if they are, use the calculatedMissingDimensions
        $newWidth = min($sourceWidth, $transformWidth ?? $width, (int)round($sourceHeight * $transformRatio));
        $newHeight = min($sourceHeight, $transformHeight ?? $height, (int)round($sourceWidth / $transformRatio));

        return [$newWidth, $newHeight];
    }

    /**
     * Returns whether an image extension is considered manipulatable.
     *
     * @param string $extension
     * @return bool
     */
    public static function canManipulateAsImage(string $extension): bool
    {
        $extension = strtolower($extension);
        if ($extension === 'heif') {
            $extension = Format::ID_HEIC;
        }

        $formats = Craft::$app->getImages()->getSupportedImageFormats();

        $alwaysManipulatable = ['svg'];
        $neverManipulatable = ['pdf', 'json', 'html', 'htm'];

        $formats = array_merge($formats, $alwaysManipulatable);
        $formats = array_diff($formats, $neverManipulatable);

        return in_array($extension, $formats);
    }

    /**
     * Returns a list of web-safe image formats.
     *
     * @return string[]
     */
    public static function webSafeFormats(): array
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp', 'avif'];
    }

    /**
     * Returns whether an extension is web-safe.
     *
     * @param string $extension
     * @return bool
     * @since 4.3.6
     */
    public static function isWebSafe(string $extension): bool
    {
        return in_array(strtolower($extension), static::webSafeFormats(), true);
    }

    /**
     * Returns any info that’s embedded in a given PNG file.
     *
     * Adapted from https://github.com/ktomk/Miscellaneous/tree/master/get_png_imageinfo.
     *
     * @param string $file The path to the PNG file.
     * @return array|false Info embedded in the PNG file, or `false` if it wasn’t found.
     * @link http://www.libpng.org/pub/png/spec/iso/index-object.html#11IHDR
     */
    public static function pngImageInfo(string $file): array|false
    {
        if (empty($file)) {
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
            6 => 'Truecolor with alpha',
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
    public static function cleanImageByPath(string $imagePath): void
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
     * @phpstan-return array{int,int}
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
        } catch (Throwable $e) {
            Craft::warning($e->getMessage(), __METHOD__);
            return [0, 0];
        }
    }

    /**
     * Determines image dimensions by a stream pointing to the start of the image.
     *
     * @param resource $stream
     * @return array|false
     * @throws TypeError
     */
    public static function imageSizeByStream($stream): array|false
    {
        if (!is_resource($stream)) {
            throw new TypeError('Argument passed should be a resource.');
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
                // Maybe WebP
                case '5249':
                    $buffer = hex2bin($signature);
                    if ($buffer === false) {
                        return false;
                    }

                    $dimensions = self::_webpSizeByStream($stream, $buffer);
                    break;
                default:
                    $buffer = hex2bin($signature);
                    if ($buffer === false) {
                        return false;
                    }

                    $dimensions = self::_isoBmffSizeByStream($stream, $buffer);
                    if ($dimensions === null) {
                        return false;
                    }
            }
        } catch (ImageException $exception) {
            Craft::info($exception->getMessage(), __METHOD__);
        }

        return $dimensions;
    }

    /**
     * @param resource $stream
     */
    private static function _webpSizeByStream($stream, string $buffer): array
    {
        $header = $buffer . stream_get_contents($stream, 12 - strlen($buffer));
        if (strlen($header) < 12 || substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WEBP') {
            throw new ImageException('Unrecognized image signature.');
        }

        $bytesRead = 12;
        while ($bytesRead < self::MAX_IMAGE_SIZE_STREAM_BYTES) {
            $chunkHeader = stream_get_contents($stream, 8);
            if (strlen($chunkHeader) < 8) {
                break;
            }

            $bytesRead += 8;
            $chunk = substr($chunkHeader, 0, 4);
            $chunkSize = unpack('V', substr($chunkHeader, 4, 4))[1];
            $paddedChunkSize = $chunkSize + ($chunkSize % 2);

            switch ($chunk) {
                case 'VP8X':
                    if ($chunkSize < 10) {
                        throw new ImageException('Unrecognized WebP file structure.');
                    }

                    $data = stream_get_contents($stream, 10);
                    if (strlen($data) < 10) {
                        throw new ImageException('Unrecognized WebP file structure.');
                    }

                    return [
                        self::_littleEndian24(substr($data, 4, 3)) + 1,
                        self::_littleEndian24(substr($data, 7, 3)) + 1,
                    ];
                case 'VP8L':
                    if ($chunkSize < 5) {
                        throw new ImageException('Unrecognized WebP file structure.');
                    }

                    $data = stream_get_contents($stream, 5);
                    if (strlen($data) < 5 || $data[0] !== "\x2F") {
                        throw new ImageException('Unrecognized WebP file structure.');
                    }

                    $bytes = unpack('C4', substr($data, 1, 4));
                    return [
                        1 + $bytes[1] + (($bytes[2] & 0x3F) << 8),
                        1 + (($bytes[2] & 0xC0) >> 6) + ($bytes[3] << 2) + (($bytes[4] & 0x0F) << 10),
                    ];
                case 'VP8 ':
                    if ($chunkSize < 10) {
                        throw new ImageException('Unrecognized WebP file structure.');
                    }

                    $data = stream_get_contents($stream, 10);
                    if (strlen($data) < 10 || substr($data, 3, 3) !== "\x9D\x01\x2A") {
                        throw new ImageException('Unrecognized WebP file structure.');
                    }

                    $dimensions = unpack('vwidth/vheight', substr($data, 6, 4));
                    return [
                        $dimensions['width'] & 0x3FFF,
                        $dimensions['height'] & 0x3FFF,
                    ];
            }

            if ($bytesRead + $paddedChunkSize > self::MAX_IMAGE_SIZE_STREAM_BYTES) {
                break;
            }

            stream_get_contents($stream, $paddedChunkSize);
            $bytesRead += $paddedChunkSize;
        }

        throw new ImageException('Unrecognized WebP file structure.');
    }

    /**
     * @param resource $stream
     */
    private static function _isoBmffSizeByStream($stream, string $buffer): ?array
    {
        $buffer .= stream_get_contents($stream, 10);
        if (strlen($buffer) < 12 || substr($buffer, 4, 4) !== 'ftyp') {
            return null;
        }

        $ftypSize = unpack('N', substr($buffer, 0, 4))[1];
        if ($ftypSize < 16 || $ftypSize > self::MAX_IMAGE_SIZE_STREAM_BYTES) {
            return null;
        }

        $buffer .= stream_get_contents($stream, $ftypSize - strlen($buffer));
        if (strlen($buffer) < $ftypSize) {
            return null;
        }

        $ftyp = substr($buffer, 8, $ftypSize - 8);
        if (!self::_isSupportedIsoBmffImage($ftyp)) {
            return null;
        }

        $buffer .= stream_get_contents($stream, self::MAX_IMAGE_SIZE_STREAM_BYTES - strlen($buffer));

        for ($offset = $ftypSize; ($box = self::_imageSizeBoxAt($buffer, $offset)) !== null; $offset = $box['endOffset']) {
            if ($box['type'] === 'meta') {
                if ($box['contentSize'] < 4) {
                    return null;
                }

                return self::_isoBmffSizeFromBoxes(substr($buffer, $box['contentOffset'] + 4, $box['contentSize'] - 4));
            }
        }

        return null;
    }

    /**
     * @return array{type:string,contentOffset:int,contentSize:int,endOffset:int}|null
     */
    private static function _imageSizeBoxAt(string $buffer, int $offset): ?array
    {
        if (strlen($buffer) < $offset + 8) {
            return null;
        }

        $size = unpack('N', substr($buffer, $offset, 4))[1];
        $contentOffset = $offset + 8;
        $endOffset = $offset + $size;

        if ($size < 8 || strlen($buffer) < $endOffset) {
            return null;
        }

        return [
            'type' => substr($buffer, $offset + 4, 4),
            'contentOffset' => $contentOffset,
            'contentSize' => $size - 8,
            'endOffset' => $endOffset,
        ];
    }

    private static function _isSupportedIsoBmffImage(string $ftyp): bool
    {
        if (strlen($ftyp) < 8) {
            return false;
        }

        $brands = [substr($ftyp, 0, 4)];
        for ($i = 8, $length = strlen($ftyp); $i + 4 <= $length; $i += 4) {
            $brands[] = substr($ftyp, $i, 4);
        }

        foreach ($brands as $brand) {
            if (in_array($brand, ['avif', 'heic', 'heif'], true)) {
                return true;
            }
        }

        return false;
    }

    private static function _isoBmffSizeFromBoxes(string $buffer): ?array
    {
        $offset = 0;
        $primaryItemId = null;
        $propertyDimensions = [];
        $ipma = null;

        while (($box = self::_imageSizeBoxAt($buffer, $offset)) !== null) {
            switch ($box['type']) {
                case 'pitm':
                    $primaryItemId = self::_isoBmffPrimaryItemId(substr($buffer, $box['contentOffset'], $box['contentSize']));
                    break;
                case 'iprp':
                    $iprp = substr($buffer, $box['contentOffset'], $box['contentSize']);
                    for ($iprpOffset = 0; ($iprpBox = self::_imageSizeBoxAt($iprp, $iprpOffset)) !== null; $iprpOffset = $iprpBox['endOffset']) {
                        switch ($iprpBox['type']) {
                            case 'ipco':
                                $propertyDimensions = self::_isoBmffPropertyDimensions(substr($iprp, $iprpBox['contentOffset'], $iprpBox['contentSize']));
                                break;
                            case 'ipma':
                                $ipma = substr($iprp, $iprpBox['contentOffset'], $iprpBox['contentSize']);
                                break;
                        }
                    }
                    break;
            }

            $offset = $box['endOffset'];
        }

        $dimensions = [];
        if ($primaryItemId !== null && $ipma !== null) {
            foreach (self::_isoBmffPrimaryPropertyIndices($ipma, $primaryItemId) as $propertyIndex) {
                if (isset($propertyDimensions[$propertyIndex])) {
                    $dimensions[] = $propertyDimensions[$propertyIndex];
                }
            }
        }

        if (count($dimensions) === 1) {
            return $dimensions[0];
        }

        if (count($dimensions) === 0 && count($propertyDimensions) === 1) {
            return reset($propertyDimensions);
        }

        return null;
    }

    private static function _isoBmffPrimaryItemId(string $buffer): ?int
    {
        if (strlen($buffer) < 6) {
            return null;
        }

        $version = ord($buffer[0]);
        if ($version === 0) {
            return unpack('n', substr($buffer, 4, 2))[1];
        }

        if ($version === 1 && strlen($buffer) >= 8) {
            return unpack('N', substr($buffer, 4, 4))[1];
        }

        return null;
    }

    private static function _isoBmffPropertyDimensions(string $buffer): array
    {
        $dimensions = [];
        $propertyIndex = 1;
        $offset = 0;

        while (($box = self::_imageSizeBoxAt($buffer, $offset)) !== null) {
            if ($box['type'] === 'ispe' && $box['contentSize'] >= 12) {
                $size = unpack('Nwidth/Nheight', substr($buffer, $box['contentOffset'] + 4, 8));
                $dimensions[$propertyIndex] = [$size['width'], $size['height']];
            }

            $propertyIndex++;
            $offset = $box['endOffset'];
        }

        return $dimensions;
    }

    private static function _isoBmffPrimaryPropertyIndices(string $buffer, int $primaryItemId): array
    {
        if (strlen($buffer) < 8) {
            return [];
        }

        $version = ord($buffer[0]);
        $entryCount = unpack('N', substr($buffer, 4, 4))[1];
        $offset = 8;

        for ($i = 0; $i < $entryCount; $i++) {
            $itemIdLength = $version < 1 ? 2 : 4;
            if (strlen($buffer) < $offset + $itemIdLength + 1) {
                return [];
            }

            $itemId = $itemIdLength === 2 ? unpack('n', substr($buffer, $offset, 2))[1] : unpack('N', substr($buffer, $offset, 4))[1];
            $offset += $itemIdLength;

            $associationCount = ord($buffer[$offset]);
            $offset++;

            $propertyIndices = [];
            $associationLength = $version < 1 ? 1 : 2;
            for ($j = 0; $j < $associationCount; $j++) {
                if (strlen($buffer) < $offset + $associationLength) {
                    return [];
                }

                if ($associationLength === 1) {
                    $propertyIndex = ord($buffer[$offset]) & 0x7F;
                } else {
                    $propertyIndex = unpack('n', substr($buffer, $offset, 2))[1] & 0x7FFF;
                }

                $offset += $associationLength;

                if ($propertyIndex !== 0) {
                    $propertyIndices[] = $propertyIndex;
                }
            }

            if ($itemId === $primaryItemId) {
                return $propertyIndices;
            }
        }

        return [];
    }

    private static function _littleEndian24(string $bytes): int
    {
        $bytes = unpack('C3', $bytes);
        return $bytes[1] + ($bytes[2] << 8) + ($bytes[3] << 16);
    }

    /**
     * Parses SVG data and determines its size (normalized to pixels).
     *
     * @param string $svg The SVG data
     * @return array [width, height]
     * @phpstan-return array{int,int}
     */
    public static function parseSvgSize(string $svg): array
    {
        if (
            preg_match(Svg::SVG_WIDTH_RE, $svg, $widthMatch) &&
            preg_match(Svg::SVG_HEIGHT_RE, $svg, $heightMatch) &&
            ($matchedWidth = (float)$widthMatch[2]) &&
            ($matchedHeight = (float)$heightMatch[2])
        ) {
            $width = (int)floor(
                $matchedWidth * self::_getSizeUnitMultiplier($widthMatch[3])
            );
            $height = (int)floor(
                $matchedHeight * self::_getSizeUnitMultiplier($heightMatch[3])
            );
        } elseif (preg_match(Svg::SVG_VIEWBOX_RE, $svg, $viewboxMatch)) {
            $width = (int)floor((float)$viewboxMatch[3]);
            $height = (int)floor((float)$viewboxMatch[4]);
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
     * @param Imagick $imagick
     */
    public static function cleanExifDataFromImagickImage(Imagick $imagick): void
    {
        $config = Craft::$app->getConfig()->getGeneral();

        if (!$config->preserveExifData) {
            $iccProfiles = null;
            /** @phpstan-ignore-next-line */
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
