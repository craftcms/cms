<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Exceptions\ImageException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Images;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\Log;
use Imagick;
use InvalidArgumentException;
use Throwable;
use TypeError;

/**
 * @since 6.0.0
 */
class ImageHelper
{
    // Bounds metadata scans for formats whose dimensions aren't in fixed header bytes.
    private const MAX_IMAGE_SIZE_STREAM_BYTES = 1024 * 1024;

    private const string JXL_CONTAINER_SIGNATURE = "\x00\x00\x00\x0CJXL \x0D\x0A\x87\x0A";

    // Bytes needed to parse a JPEG XL SizeHeader (68 bits max), plus the 2-byte codestream signature
    private const int JXL_SIZE_HEADER_BYTES = 11;

    /** @return array{int,int} */
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

    /** @return array{int,int} */
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
            $extension = 'heic';
        }

        $formats = Images::getSupportedImageFormats();

        $alwaysManipulatable = ['svg'];
        $neverManipulatable = ['pdf', 'json', 'html', 'htm'];

        $formats = array_merge($formats, $alwaysManipulatable);
        $formats = array_diff($formats, $neverManipulatable);

        return in_array($extension, $formats);
    }

    /** @return list<string> */
    public static function webSafeFormats(): array
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp', 'avif'];
    }

    public static function isWebSafe(string $extension): bool
    {
        return in_array(strtolower($extension), self::webSafeFormats(), true);
    }

    /** @return array<string,int|string>|false */
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

    /** @return array{int,int} */
    public static function imageSize(string $filePath): array
    {
        try {
            if (File::isSvg($filePath)) {
                return self::parseSvgSize(file_get_contents($filePath));
            }

            $image = Images::loadImage($filePath);

            return [$image->getWidth(), $image->getHeight()];
        } catch (Throwable $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return [0, 0];
        }
    }

    /**
     * @param  resource  $stream
     * @return array{int,int}|array{}|false
     */
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
                    // Maybe BMP
                case '424D':
                    $dimensions = self::bmpSize(stream_get_contents($stream, 24));
                    if ($dimensions === null) {
                        return false;
                    }
                    break;
                    // Maybe WebP
                case '5249':
                    $buffer = hex2bin($signature);
                    if ($buffer === false) {
                        return false;
                    }

                    $dimensions = self::webpSizeByStream($stream, $buffer);
                    break;
                    // Maybe JPEG XL (bare codestream)
                case 'FF0A':
                    $dimensions = self::jxlCodestreamSize(hex2bin($signature).stream_get_contents($stream, self::JXL_SIZE_HEADER_BYTES - 2));
                    if ($dimensions === null) {
                        return false;
                    }
                    break;
                default:
                    $buffer = hex2bin($signature);
                    if ($buffer === false) {
                        return false;
                    }

                    $buffer .= stream_get_contents($stream, 12 - strlen($buffer));

                    // Maybe JPEG XL (ISO BMFF container)
                    if ($buffer === self::JXL_CONTAINER_SIGNATURE) {
                        $dimensions = self::jxlContainerSizeByStream($stream);
                    } else {
                        $dimensions = self::isoBmffSizeByStream($stream, $buffer);
                    }

                    if ($dimensions === null) {
                        return false;
                    }
            }
        } catch (ImageException $exception) {
            Log::info($exception->getMessage(), [__METHOD__]);
        }

        return $dimensions;
    }

    /**
     * @param  resource  $stream
     * @return array{int,int}
     */
    private static function webpSizeByStream($stream, string $buffer): array
    {
        $header = $buffer.stream_get_contents($stream, 12 - strlen($buffer));
        if (strlen($header) < 12 || ! str_starts_with($header, 'RIFF') || substr($header, 8, 4) !== 'WEBP') {
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
                        self::littleEndian24(substr($data, 4, 3)) + 1,
                        self::littleEndian24(substr($data, 7, 3)) + 1,
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
     * @param  resource  $stream
     * @return array{int,int}|null
     */
    private static function isoBmffSizeByStream($stream, string $buffer): ?array
    {
        $buffer .= stream_get_contents($stream, 12 - strlen($buffer));
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
        if (! self::isSupportedIsoBmffImage($ftyp)) {
            return null;
        }

        $buffer .= stream_get_contents($stream, self::MAX_IMAGE_SIZE_STREAM_BYTES - strlen($buffer));

        for ($offset = $ftypSize; ($box = self::imageSizeBoxAt($buffer, $offset)) !== null; $offset = $box['endOffset']) {
            if ($box['type'] === 'meta') {
                if ($box['contentSize'] < 4) {
                    return null;
                }

                return self::isoBmffSizeFromBoxes(substr($buffer, $box['contentOffset'] + 4, $box['contentSize'] - 4));
            }
        }

        return null;
    }

    /**
     * Returns a BMP’s dimensions from the bytes following its 2-byte signature.
     *
     * @return array{int,int}|null
     */
    private static function bmpSize(string $buffer): ?array
    {
        // The DIB header starts after the remaining 12 bytes of the file header, and begins with its own size
        if (strlen($buffer) < 16) {
            return null;
        }

        $dibHeaderSize = unpack('V', substr($buffer, 12, 4))[1];

        if ($dibHeaderSize === 12) {
            // BITMAPCOREHEADER (OS/2 1.x): unsigned 16-bit dimensions
            $width = unpack('v', substr($buffer, 16, 2))[1];
            $height = unpack('v', substr($buffer, 18, 2))[1];
        } elseif ($dibHeaderSize >= 40 && strlen($buffer) >= 24) {
            // BITMAPINFOHEADER and later: signed 32-bit dimensions, where a negative height means top-down
            [$width, $height] = array_map(
                fn (int $value) => $value >= 0x80000000 ? $value - 0x100000000 : $value,
                array_values(unpack('V2', substr($buffer, 16, 8))),
            );
            $height = abs($height);
        } else {
            return null;
        }

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return [$width, $height];
    }

    /**
     * Reads the beginning of the codestream out of a JPEG XL container’s `jxlc`/`jxlp` boxes,
     * and returns its dimensions.
     *
     * @param  resource  $stream  A stream positioned right after the container signature box
     * @return array{int,int}|null
     */
    private static function jxlContainerSizeByStream($stream): ?array
    {
        $codestream = '';
        $bytesRead = 12;

        while (strlen($codestream) < self::JXL_SIZE_HEADER_BYTES) {
            $header = stream_get_contents($stream, 8);
            if (strlen($header) < 8) {
                return null;
            }

            $size = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);
            $bytesRead += 8;

            if ($size === 1) {
                // 64-bit box size
                $largeSize = stream_get_contents($stream, 8);
                if (strlen($largeSize) < 8) {
                    return null;
                }
                $bytesRead += 8;
                $contentSize = unpack('J', $largeSize)[1] - 16;
            } elseif ($size === 0) {
                // Box extends to the end of the file
                $contentSize = PHP_INT_MAX;
            } else {
                $contentSize = $size - 8;
            }

            if ($contentSize < 0) {
                return null;
            }

            if ($type === 'jxlc' || $type === 'jxlp') {
                if ($type === 'jxlp') {
                    // Skip the partial codestream box index
                    if ($contentSize < 4 || strlen(stream_get_contents($stream, 4)) < 4) {
                        return null;
                    }
                    $contentSize -= 4;
                }

                $chunk = stream_get_contents($stream, min($contentSize, self::JXL_SIZE_HEADER_BYTES - strlen($codestream)));
                $codestream .= $chunk;

                // jxlc holds the full codestream, so there's nothing more to read
                if ($type === 'jxlc' || strlen($chunk) < $contentSize) {
                    break;
                }
            } else {
                if ($size === 0 || $bytesRead + $contentSize > self::MAX_IMAGE_SIZE_STREAM_BYTES) {
                    return null;
                }

                if ($contentSize > 0 && strlen(stream_get_contents($stream, $contentSize)) < $contentSize) {
                    return null;
                }
            }

            $bytesRead += $contentSize;
        }

        return self::jxlCodestreamSize($codestream);
    }

    /**
     * Parses the SizeHeader at the beginning of a JPEG XL codestream (ISO/IEC 18181-1 §A.3.1).
     *
     * Note that the returned dimensions don’t account for the image’s orientation, consistent with JPEG.
     *
     * @param  string  $codestream  The beginning of the codestream, including its signature
     * @return array{int,int}|null
     */
    private static function jxlCodestreamSize(string $codestream): ?array
    {
        if (strlen($codestream) < self::JXL_SIZE_HEADER_BYTES || ! str_starts_with($codestream, "\xFF\x0A")) {
            return null;
        }

        // Bits are packed least-significant-bit first
        $position = 16;
        $read = function (int $bits) use ($codestream, &$position): int {
            $value = 0;
            for ($i = 0; $i < $bits; $i++, $position++) {
                $bit = (ord($codestream[$position >> 3]) >> ($position & 7)) & 1;
                $value |= $bit << $i;
            }

            return $value;
        };
        $readSize = fn () => $read([9, 13, 18, 30][$read(2)]) + 1;

        $div8 = $read(1) === 1;
        $height = $div8 ? ($read(5) + 1) * 8 : $readSize();
        $ratio = $read(3);

        if ($ratio === 0) {
            $width = $div8 ? ($read(5) + 1) * 8 : $readSize();
        } else {
            [$numerator, $denominator] = [
                1 => [1, 1],
                2 => [12, 10],
                3 => [4, 3],
                4 => [3, 2],
                5 => [16, 9],
                6 => [5, 4],
                7 => [2, 1],
            ][$ratio];
            $width = intdiv($height * $numerator, $denominator);
        }

        return [$width, $height];
    }

    /**
     * @return array{type:string,contentOffset:int,contentSize:int,endOffset:int}|null
     */
    private static function imageSizeBoxAt(string $buffer, int $offset): ?array
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

    private static function isSupportedIsoBmffImage(string $ftyp): bool
    {
        if (strlen($ftyp) < 8) {
            return false;
        }

        $brands = [substr($ftyp, 0, 4)];
        for ($i = 8, $length = strlen($ftyp); $i + 4 <= $length; $i += 4) {
            $brands[] = substr($ftyp, $i, 4);
        }

        return array_any($brands, fn ($brand) => in_array($brand, ['avif', 'heic', 'heif'], true));
    }

    /** @return array{int,int}|null */
    private static function isoBmffSizeFromBoxes(string $buffer): ?array
    {
        $offset = 0;
        $primaryItemId = null;
        $propertyDimensions = [];
        $ipma = null;

        while (($box = self::imageSizeBoxAt($buffer, $offset)) !== null) {
            switch ($box['type']) {
                case 'pitm':
                    $primaryItemId = self::isoBmffPrimaryItemId(substr($buffer, $box['contentOffset'], $box['contentSize']));
                    break;
                case 'iprp':
                    $iprp = substr($buffer, $box['contentOffset'], $box['contentSize']);
                    for ($iprpOffset = 0; ($iprpBox = self::imageSizeBoxAt($iprp, $iprpOffset)) !== null; $iprpOffset = $iprpBox['endOffset']) {
                        switch ($iprpBox['type']) {
                            case 'ipco':
                                $propertyDimensions = self::isoBmffPropertyDimensions(substr($iprp, $iprpBox['contentOffset'], $iprpBox['contentSize']));
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
            foreach (self::isoBmffPrimaryPropertyIndices($ipma, $primaryItemId) as $propertyIndex) {
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

    private static function isoBmffPrimaryItemId(string $buffer): ?int
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

    /** @return array<int,array{int,int}> */
    private static function isoBmffPropertyDimensions(string $buffer): array
    {
        $dimensions = [];
        $propertyIndex = 1;
        $offset = 0;

        while (($box = self::imageSizeBoxAt($buffer, $offset)) !== null) {
            if ($box['type'] === 'ispe' && $box['contentSize'] >= 12) {
                $size = unpack('Nwidth/Nheight', substr($buffer, $box['contentOffset'] + 4, 8));
                $dimensions[$propertyIndex] = [$size['width'], $size['height']];
            }

            $propertyIndex++;
            $offset = $box['endOffset'];
        }

        return $dimensions;
    }

    /** @return list<int> */
    private static function isoBmffPrimaryPropertyIndices(string $buffer, int $primaryItemId): array
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

    private static function littleEndian24(string $bytes): int
    {
        $bytes = unpack('C3', $bytes);

        return $bytes[1] + ($bytes[2] << 8) + ($bytes[3] << 16);
    }

    /** @return array{int,int} */
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
