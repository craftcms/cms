<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

/**
 * Builds and smooths grids of an image's region colors.
 *
 * Colors are mixed in linear light, weighted by opacity, so a region of fine black and white detail averages to the
 * gray it reads as, rather than a darker one, and transparent pixels don't tint what they're mixed with.
 *
 * @see Images::colors()
 * @see Data\ImageColors::$grid
 */
class ColorGrid
{
    /**
     * Averages an image's pixels into a grid of regions.
     *
     * Pixels that straddle a region's edge count toward it by how much of them it covers.
     *
     * @param  list<array{int, int, int, float}>  $pixels  The image's pixels, row by row, as red, green, and blue values
     *                                                     from 0 to 255, and an alpha value from 0 to 1
     * @return list<list<string>> Rows of hex colors, top to bottom
     */
    public static function average(array $pixels, int $width, int $height, int $columns, int $rows): array
    {
        $grid = [];

        for ($row = 0; $row < $rows; $row++) {
            $top = $row * $height / $rows;
            $bottom = ($row + 1) * $height / $rows;
            $cells = [];

            for ($column = 0; $column < $columns; $column++) {
                $left = $column * $width / $columns;
                $right = ($column + 1) * $width / $columns;
                $weights = 0.0;
                $sums = [0.0, 0.0, 0.0, 0.0];

                for ($y = (int) floor($top); $y < (int) ceil($bottom); $y++) {
                    $coverageY = min($y + 1, $bottom) - max($y, $top);

                    for ($x = (int) floor($left); $x < (int) ceil($right); $x++) {
                        $weight = (min($x + 1, $right) - max($x, $left)) * $coverageY;
                        $weights += $weight;
                        $sums = self::add($sums, self::premultiplied($pixels[$y * $width + $x]), $weight);
                    }
                }

                $cells[] = self::hex(array_map(fn (float $sum): float => $sum / $weights, $sums));
            }

            $grid[] = $cells;
        }

        return $grid;
    }

    /**
     * Scales a grid up with Catmull-Rom interpolation between the regions' centers, so it can be scaled up further
     * without the creases that bilinear scaling leaves between regions.
     *
     * @param  list<list<string>>  $grid  Rows of hex colors
     * @return list<list<string>> Rows of hex colors, `$scale` times as many in each direction
     */
    public static function interpolate(array $grid, int $scale): array
    {
        $colors = array_map(fn (array $row): array => array_map(
            fn (string $color): array => self::premultiplied(self::parse($color)),
            $row,
        ), $grid);
        $rows = count($colors);
        $columns = count($colors[0] ?? []);

        // Interpolating across then down is the same as a bicubic filter, for a fraction of the work.
        $wide = array_map(fn (array $row): array => self::interpolateLine($row, $columns * $scale, $scale), $colors);
        $output = array_fill(0, $rows * $scale, []);

        for ($x = 0; $x < $columns * $scale; $x++) {
            $column = self::interpolateLine(array_column($wide, $x), $rows * $scale, $scale);

            foreach ($column as $y => $color) {
                $output[$y][$x] = self::hex($color);
            }
        }

        return $output;
    }

    /**
     * @param  list<array{float, float, float, float}>  $line
     * @return list<array{float, float, float, float}>
     */
    private static function interpolateLine(array $line, int $length, int $scale): array
    {
        $last = count($line) - 1;
        $output = [];

        for ($index = 0; $index < $length; $index++) {
            // Where this output pixel's center falls between the input's region centers
            $position = ($index + 0.5) / $scale - 0.5;
            $start = (int) floor($position);
            $t = $position - $start;
            $weights = [
                ((-$t + 2) * $t - 1) * $t / 2,
                ((3 * $t - 5) * $t * $t + 2) / 2,
                ((-3 * $t + 4) * $t + 1) * $t / 2,
                ($t - 1) * $t * $t / 2,
            ];
            $sums = [0.0, 0.0, 0.0, 0.0];

            foreach ($weights as $offset => $weight) {
                $sums = self::add($sums, $line[max(0, min($last, $start - 1 + $offset))], $weight);
            }

            $output[] = $sums;
        }

        return $output;
    }

    /**
     * @param  array{float, float, float, float}  $sums
     * @param  array{float, float, float, float}  $color
     * @return array{float, float, float, float}
     */
    private static function add(array $sums, array $color, float $weight): array
    {
        return [
            $sums[0] + $color[0] * $weight,
            $sums[1] + $color[1] * $weight,
            $sums[2] + $color[2] * $weight,
            $sums[3] + $color[3] * $weight,
        ];
    }

    /**
     * @param  array{int, int, int, float}  $color
     * @return array{float, float, float, float} Linear red, green, and blue, multiplied by alpha, then alpha
     */
    private static function premultiplied(array $color): array
    {
        [$red, $green, $blue, $alpha] = $color;

        return [
            self::toLinear($red) * $alpha,
            self::toLinear($green) * $alpha,
            self::toLinear($blue) * $alpha,
            $alpha,
        ];
    }

    /** @return array{int, int, int, float} */
    private static function parse(string $hex): array
    {
        return [
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
            strlen($hex) > 7 ? hexdec(substr($hex, 7, 2)) / 255 : 1.0,
        ];
    }

    /**
     * Formats a premultiplied linear color as `#rrggbb`, or `#rrggbbaa` if it isn't fully opaque.
     *
     * @param  array{float, float, float, float}  $color
     */
    private static function hex(array $color): string
    {
        // Unpremultiplied by the unclamped alpha, so an interpolated alpha that overshoots 1 doesn't brighten the color.
        $channels = array_map(
            fn (float $channel): int => $color[3] > 0 ? self::toSrgb($channel / $color[3]) : 0,
            [$color[0], $color[1], $color[2]],
        );
        $alpha = max(0.0, min(1.0, $color[3]));
        $alphaByte = (int) round($alpha * 255);

        if ($alphaByte < 255) {
            $channels[] = $alphaByte;
        }

        return '#'.implode('', array_map(fn (int $channel): string => sprintf('%02x', $channel), $channels));
    }

    private static function toLinear(int $channel): float
    {
        $value = $channel / 255;

        return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }

    private static function toSrgb(float $linear): int
    {
        $linear = max(0.0, min(1.0, $linear));
        $value = $linear <= 0.0031308 ? $linear * 12.92 : 1.055 * $linear ** (1 / 2.4) - 0.055;

        return (int) round($value * 255);
    }
}
