<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

/**
 * Fingerprints images so copies of the same image can be found, even once they’ve been resized, recompressed, or
 * saved in another format.
 *
 * The fingerprint is a 64-bit [difference hash](https://www.hackerfactor.com/blog/index.php?/archives/529-Kind-of-Like-That.html):
 * the image is averaged down to 9×8 regions of lightness, and each bit says whether a region is lighter than the one
 * to its right. Similar images have hashes that differ in only a few bits, which {@see distance()} counts.
 *
 * @see Images::sample()
 * @since 6.0.0
 */
class ImageHash
{
    private const int COLUMNS = 9;

    private const int ROWS = 8;

    /**
     * Returns an image’s difference hash, as 16 hexadecimal digits.
     *
     * Pixels that straddle a region’s edge count toward it by how much of them it covers, and transparent pixels are
     * treated as white.
     *
     * @param  list<array{int, int, int, float}>  $pixels  The image’s pixels, row by row, as red, green, and blue values
     *                                                     from 0 to 255, and an alpha value from 0 to 1
     */
    public static function dhash(array $pixels, int $width, int $height): string
    {
        $lightness = [];

        for ($row = 0; $row < self::ROWS; $row++) {
            $top = $row * $height / self::ROWS;
            $bottom = ($row + 1) * $height / self::ROWS;

            for ($column = 0; $column < self::COLUMNS; $column++) {
                $left = $column * $width / self::COLUMNS;
                $right = ($column + 1) * $width / self::COLUMNS;
                $weights = 0.0;
                $sum = 0.0;

                for ($y = (int) floor($top); $y < (int) ceil($bottom); $y++) {
                    $coverageY = min($y + 1, $bottom) - max($y, $top);

                    for ($x = (int) floor($left); $x < (int) ceil($right); $x++) {
                        $weight = (min($x + 1, $right) - max($x, $left)) * $coverageY;
                        [$red, $green, $blue, $alpha] = $pixels[$y * $width + $x];
                        $weights += $weight;
                        $sum += (ColorGrid::relativeLuminance($red, $green, $blue) * $alpha + 1 - $alpha) * $weight;
                    }
                }

                $lightness[$row][$column] = $weights > 0 ? $sum / $weights : 1.0;
            }
        }

        $hex = '';

        for ($row = 0; $row < self::ROWS; $row++) {
            $nibble = 0;

            for ($column = 0; $column < self::COLUMNS - 1; $column++) {
                $nibble = ($nibble << 1) | ($lightness[$row][$column] < $lightness[$row][$column + 1] ? 1 : 0);

                if ($column % 4 === 3) {
                    $hex .= dechex($nibble);
                    $nibble = 0;
                }
            }
        }

        return $hex;
    }

    /**
     * Returns how many bits two hashes differ in, from 0 (the same image) to 64. Copies of an image usually differ in
     * up to 5 or so.
     */
    public static function distance(string $a, string $b): int
    {
        $distance = 0;

        for ($i = 0; $i < 16; $i++) {
            $distance += substr_count(decbin(hexdec($a[$i] ?? '0') ^ hexdec($b[$i] ?? '0')), '1');
        }

        return $distance;
    }

    /**
     * Returns whether a value is a hash, as {@see dhash()} returns them.
     */
    public static function isHash(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[0-9a-f]{16}$/', $value) === 1;
    }
}
