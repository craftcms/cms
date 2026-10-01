<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Elements\Asset;
use InvalidArgumentException;

/**
 * Encodes [BlurHash](https://blurha.sh) strings, following the reference implementation
 * ([woltapp/blurhash](https://github.com/woltapp/blurhash)).
 *
 * @see Asset::getBlurhash()
 * @since 6.0.0
 */
class Blurhash
{
    private const string CHARACTERS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz#$%*+,-.:;=?@[]^_{|}~';

    /**
     * Encodes an image as a BlurHash string.
     *
     * BlurHash has no alpha channel, so colors that aren't fully opaque are blended over white.
     *
     * @param  list<list<string>>  $pixels  The image's pixels, as rows of hex colors (`#rrggbb` or `#rrggbbaa`), top
     *                                      to bottom
     * @param  int  $componentsX  How many components to encode across, from 1 to 9
     * @param  int  $componentsY  How many components to encode down, from 1 to 9
     */
    public static function encode(array $pixels, int $componentsX, int $componentsY): string
    {
        if ($componentsX < 1 || $componentsX > 9 || $componentsY < 1 || $componentsY > 9) {
            throw new InvalidArgumentException('BlurHash components must be between 1 and 9.');
        }

        $height = count($pixels);
        $width = count($pixels[0] ?? []);

        if ($width === 0) {
            throw new InvalidArgumentException('BlurHash needs at least one pixel to encode.');
        }

        $linear = array_map(fn (array $row): array => array_map(self::linearColor(...), $row), $pixels);
        $factors = [];

        for ($componentY = 0; $componentY < $componentsY; $componentY++) {
            for ($componentX = 0; $componentX < $componentsX; $componentX++) {
                $normalisation = $componentX === 0 && $componentY === 0 ? 1 : 2;
                $sums = [0.0, 0.0, 0.0];

                foreach ($linear as $y => $row) {
                    $basisY = cos(M_PI * $componentY * $y / $height);

                    foreach ($row as $x => $color) {
                        $basis = $normalisation * cos(M_PI * $componentX * $x / $width) * $basisY;
                        $sums[0] += $basis * $color[0];
                        $sums[1] += $basis * $color[1];
                        $sums[2] += $basis * $color[2];
                    }
                }

                $factors[] = array_map(fn (float $sum): float => $sum / ($width * $height), $sums);
            }
        }

        $dc = array_shift($factors);
        $hash = self::base83(($componentsX - 1) + ($componentsY - 1) * 9, 1);

        if ($factors === []) {
            $maximumValue = 1.0;
            $hash .= self::base83(0, 1);
        } else {
            $actualMaximum = max(array_map(fn (array $factor): float => max(array_map(abs(...), $factor)), $factors));
            $quantisedMaximum = (int) max(0, min(82, floor($actualMaximum * 166 - 0.5)));
            $maximumValue = ($quantisedMaximum + 1) / 166;
            $hash .= self::base83($quantisedMaximum, 1);
        }

        $hash .= self::base83((ColorGrid::toSrgb($dc[0]) << 16) + (ColorGrid::toSrgb($dc[1]) << 8) + ColorGrid::toSrgb($dc[2]), 4);

        foreach ($factors as $factor) {
            [$red, $green, $blue] = array_map(
                fn (float $value): int => (int) max(0, min(18, floor(self::signPow($value / $maximumValue, 0.5) * 9 + 9.5))),
                $factor,
            );
            $hash .= self::base83($red * 19 * 19 + $green * 19 + $blue, 2);
        }

        return $hash;
    }

    /**
     * Returns a hex color's linear red, green, and blue values, blended over white if it isn't fully opaque.
     *
     * @return array{float, float, float}
     */
    private static function linearColor(string $hex): array
    {
        [$red, $green, $blue, $alpha] = ColorGrid::parse($hex);

        return array_map(
            fn (int $channel): float => ColorGrid::toLinear($channel) * $alpha + 1 - $alpha,
            [$red, $green, $blue],
        );
    }

    private static function signPow(float $value, float $exponent): float
    {
        return ($value < 0 ? -1 : 1) * abs($value) ** $exponent;
    }

    private static function base83(int $value, int $length): string
    {
        $result = '';

        for ($i = 1; $i <= $length; $i++) {
            $result .= self::CHARACTERS[intdiv($value, 83 ** ($length - $i)) % 83];
        }

        return $result;
    }
}
