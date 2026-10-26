<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use function CraftCms\Cms\t;

/**
 * Names colors in plain words, such as “red” or “teal”, so images can be found by the colors in them.
 *
 * Colors are compared in Oklch, whose lightness and hue match how people see colors: black, white, and gray by
 * lightness and chroma, brown for dark oranges and yellows, and otherwise by hue.
 *
 * @since 6.0.0
 */
class ColorNames
{
    /** @var list<string> Every name a color can have. */
    public const array NAMES = ['black', 'white', 'gray', 'brown', 'red', 'orange', 'yellow', 'green', 'teal', 'blue', 'purple', 'pink'];

    /** Upper hue bounds, in degrees, of the hue-named colors. Hues from the last bound around to 10° are pink. */
    private const array HUES = [
        10 => 'pink',
        45 => 'red',
        75 => 'orange',
        115 => 'yellow',
        165 => 'green',
        220 => 'teal',
        275 => 'blue',
        335 => 'purple',
        360 => 'pink',
    ];

    /**
     * Returns the name of a hex color, such as `#3a6ea5`.
     */
    public static function name(string $hex): string
    {
        [$lightness, $chroma, $hue] = self::oklch($hex);

        return match (true) {
            $lightness < 0.25 => 'black',
            $chroma < 0.035 => $lightness > 0.9 ? 'white' : 'gray',
            $hue >= 30 && $hue < 100 && $lightness < 0.55 => 'brown',
            default => self::hueName($hue),
        };
    }

    /**
     * Returns a color name, such as `red`, translated for display or search, such as “Rot” in German.
     */
    public static function label(string $name, ?string $locale = null): string
    {
        return match ($name) {
            'black' => t('Black', locale: $locale),
            'white' => t('White', locale: $locale),
            'gray' => t('Gray', locale: $locale),
            'brown' => t('Brown', locale: $locale),
            'red' => t('Red', locale: $locale),
            'orange' => t('Orange', locale: $locale),
            'yellow' => t('Yellow', locale: $locale),
            'green' => t('Green', locale: $locale),
            'teal' => t('Teal', locale: $locale),
            'blue' => t('Blue', locale: $locale),
            'purple' => t('Purple', locale: $locale),
            'pink' => t('Pink', locale: $locale),
            default => $name,
        };
    }

    private static function hueName(float $hue): string
    {
        foreach (self::HUES as $bound => $name) {
            if ($hue < $bound) {
                return $name;
            }
        }

        return 'pink';
    }

    /**
     * Converts a hex color to Oklch lightness (0–1), chroma, and hue (0–360°).
     *
     * @return array{float, float, float}
     */
    private static function oklch(string $hex): array
    {
        [$red, $green, $blue] = array_map(
            static function (int $channel): float {
                $value = $channel / 255;

                return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            },
            array_slice(ColorGrid::parse($hex), 0, 3),
        );

        $l = (0.4122214708 * $red + 0.5363325363 * $green + 0.0514459929 * $blue) ** (1 / 3);
        $m = (0.2119034982 * $red + 0.6806995451 * $green + 0.1073969566 * $blue) ** (1 / 3);
        $s = (0.0883024619 * $red + 0.2817188376 * $green + 0.6299787005 * $blue) ** (1 / 3);

        $lightness = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $b = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;
        $hue = rad2deg(atan2($b, $a));

        return [$lightness, sqrt($a ** 2 + $b ** 2), $hue < 0 ? $hue + 360 : $hue];
    }
}
