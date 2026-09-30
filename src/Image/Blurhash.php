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

        $hash .= self::base83((self::toSrgb($dc[0]) << 16) + (self::toSrgb($dc[1]) << 8) + self::toSrgb($dc[2]), 4);

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
        $alpha = strlen($hex) > 7 ? hexdec(substr($hex, 7, 2)) / 255 : 1.0;

        return [
            self::toLinear((int) hexdec(substr($hex, 1, 2))) * $alpha + 1 - $alpha,
            self::toLinear((int) hexdec(substr($hex, 3, 2))) * $alpha + 1 - $alpha,
            self::toLinear((int) hexdec(substr($hex, 5, 2))) * $alpha + 1 - $alpha,
        ];
    }

    private static function toLinear(int $channel): float
    {
        $value = $channel / 255;

        return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }

    private static function toSrgb(float $linear): int
    {
        $value = max(0.0, min(1.0, $linear));

        return (int) ($value <= 0.0031308
            ? $value * 12.92 * 255 + 0.5
            : (1.055 * $value ** (1 / 2.4) - 0.055) * 255 + 0.5);
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
