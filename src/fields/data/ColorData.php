<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use craft\base\Model;
use craft\base\Serializable;
use craft\validators\ColorValidator;
use craft\web\twig\AllowedInSandbox;

/**
 * Multi-select option field data class.
 *
 * @property string $hex
 * @property string $rgb
 * @property string $rgba
 * @property string $rgbChannels
 * @property string $oklch
 * @property string $contrastColor
 * @property int $red
 * @property int $green
 * @property int $blue
 * @property int $r
 * @property int $g
 * @property int $b
 * @property float $alpha
 * @property float $luma
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Top Shelf Craft <michael@michaelrog.com>
 * @since 3.0.0
 */
class ColorData extends Model implements Serializable
{
    /**
     * @var string|null The human-facing label for the color.
     * @since 5.7.0
     */
    public ?string $label = null;

    /**
     * @var string The color’s hex value
     */
    private string $_hex;

    /**
     * @var array
     * @see _hsl()
     */
    private array $_hsl;

    /**
     * Constructor.
     *
     * @param string $hex hex color value, beginning with `#`, optionally including an alpha channel (e.g. `#ff000080`).
     * (Shorthand is not supported, e.g. `#f00`.)
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(string $hex, array $config = [])
    {
        $this->_hex = $hex;
        parent::__construct($config);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->_hex;
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'alpha';
        $attributes[] = 'blue';
        $attributes[] = 'green';
        $attributes[] = 'hex';
        $attributes[] = 'luma';
        $attributes[] = 'red';
        $attributes[] = 'rgb';
        $attributes[] = 'rgba';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function serialize(): mixed
    {
        return $this->_hex;
    }

    /**
     * Returns the color as a hex.
     *
     * The value will only be 8 digits long if the color has an alpha channel that isn’t fully opaque.
     *
     * @param bool $withAlpha Whether to include the alpha channel, if the color has one (since 5.12.0)
     * @return string
     */
    #[AllowedInSandbox]
    public function getHex(bool $withAlpha = true): string
    {
        if (!$withAlpha && strlen($this->_hex) === 9) {
            return substr($this->_hex, 0, 7);
        }

        return $this->_hex;
    }

    /**
     * Returns the color in `rgb()` syntax.
     *
     * @return string
     */
    #[AllowedInSandbox]
    public function getRgb(): string
    {
        return "rgb({$this->getRed()},{$this->getGreen()},{$this->getBlue()})";
    }

    /**
     * Returns the color in `rgba()` syntax.
     *
     * @return string
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getRgba(): string
    {
        return "rgba({$this->getRed()},{$this->getGreen()},{$this->getBlue()},{$this->getAlpha()})";
    }

    /**
     * Returns the color in `hsl()` syntax.
     *
     * @return string
     * @since 3.7.26
     */
    #[AllowedInSandbox]
    public function getHsl(): string
    {
        [$h, $s, $l] = $this->_hsl();
        return "hsl($h,$s%,$l%)";
    }

    /**
     * Returns the color in `hsla()` syntax.
     *
     * @return string
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getHsla(): string
    {
        [$h, $s, $l] = $this->_hsl();
        return "hsla($h,$s%,$l%,{$this->getAlpha()})";
    }

    /**
     * Returns the color’s red, green, and blue channels, separated by spaces (e.g. `255 128 0`).
     *
     * This can be used to define a CSS custom property that’s used within `rgb()`, e.g. `rgb(var(--brand) / 50%)`.
     *
     * @return string
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getRgbChannels(): string
    {
        return "{$this->getRed()} {$this->getGreen()} {$this->getBlue()}";
    }

    /**
     * Returns the color in `oklch()` syntax (e.g. `oklch(62.8% 0.2577 29.23)`).
     *
     * The alpha channel is included if the color isn’t fully opaque (e.g. `oklch(62.8% 0.2577 29.23 / 0.5)`).
     *
     * @return string
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getOklch(): string
    {
        // h/t https://bottosson.github.io/posts/oklab/
        [$r, $g, $b] = $this->_linearRgb();
        $l = pow(0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b, 1 / 3);
        $m = pow(0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b, 1 / 3);
        $s = pow(0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b, 1 / 3);

        $okL = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $okA = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $okB = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

        $lightness = round($okL * 100, 2) + 0;
        $chroma = round(sqrt($okA ** 2 + $okB ** 2), 4) + 0;
        // Achromatic colors don’t have a meaningful hue
        $hue = $chroma == 0 ? 0 : round(fmod(rad2deg(atan2($okB, $okA)) + 360, 360), 2) + 0;

        $alpha = $this->getAlpha();
        return sprintf('oklch(%s%% %s %s%s)', $lightness, $chroma, $hue, $alpha < 1 ? " / $alpha" : '');
    }

    /**
     * @return int
     */
    #[AllowedInSandbox]
    public function getRed(): int
    {
        return hexdec(substr($this->_hex, 1, 2));
    }

    /**
     * @return int
     */
    #[AllowedInSandbox]
    public function getR(): int
    {
        return $this->getRed();
    }

    /**
     * @return int
     */
    #[AllowedInSandbox]
    public function getGreen(): int
    {
        return hexdec(substr($this->_hex, 3, 2));
    }

    /**
     * @return int
     */
    #[AllowedInSandbox]
    public function getG(): int
    {
        return $this->getGreen();
    }

    /**
     * @return int
     */
    #[AllowedInSandbox]
    public function getBlue(): int
    {
        return hexdec(substr($this->_hex, 5, 2));
    }

    /**
     * @return int
     */
    #[AllowedInSandbox]
    public function getB(): int
    {
        return $this->getBlue();
    }

    /**
     * Returns the color’s alpha channel, as a number between 0 (fully transparent) and 1 (fully opaque).
     *
     * @return float
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getAlpha(): float
    {
        if (strlen($this->_hex) !== 9) {
            return 1.0;
        }

        // Use two decimal places if that’s enough to get back to the same 8-bit value
        $alpha = hexdec(substr($this->_hex, 7, 2));
        $rounded = round($alpha / 255, 2);
        return (int)round($rounded * 255) === $alpha ? $rounded : round($alpha / 255, 3);
    }

    /**
     * @return int
     * @since 3.7.26
     */
    #[AllowedInSandbox]
    public function getHue(): int
    {
        return $this->_hsl()[0];
    }

    /**
     * @return int
     * @since 3.7.26
     */
    #[AllowedInSandbox]
    public function getH(): int
    {
        return $this->getHue();
    }

    /**
     * @return int
     * @since 3.7.26
     */
    #[AllowedInSandbox]
    public function getSaturation(): int
    {
        return $this->_hsl()[1];
    }

    /**
     * @return int
     * @since 3.7.26
     */
    #[AllowedInSandbox]
    public function getS(): int
    {
        return $this->getSaturation();
    }

    /**
     * @return int
     * @since 3.7.26
     */
    #[AllowedInSandbox]
    public function getLightness(): int
    {
        return $this->_hsl()[2];
    }

    /**
     * @return int
     * @since 3.7.26
     */
    #[AllowedInSandbox]
    public function getL(): int
    {
        return $this->getLightness();
    }

    /**
     * @return array
     */
    private function _hsl(): array
    {
        if (!isset($this->_hsl)) {
            // h/t https://gist.github.com/brandonheyer/5254516
            $rPct = $this->getRed() / 255;
            $gPct = $this->getGreen() / 255;
            $bPct = $this->getBlue() / 255;

            $maxRgb = max($rPct, $gPct, $bPct);
            $minRgb = min($rPct, $gPct, $bPct);

            $l = ($maxRgb + $minRgb) / 2;
            $d = $maxRgb - $minRgb;

            if ($d == 0) {
                $h = $s = 0; // achromatic
            } else {
                $s = $d / (1 - abs(2 * $l - 1));

                switch ($maxRgb) {
                    case $rPct:
                        $h = 60 * fmod((($gPct - $bPct) / $d), 6);
                        if ($bPct > $gPct) {
                            $h += 360;
                        }
                        break;

                    case $gPct:
                        $h = 60 * (($bPct - $rPct) / $d + 2);
                        break;

                    default:
                        $h = 60 * (($rPct - $gPct) / $d + 4);
                        break;
                }
            }

            $this->_hsl = [round($h), round($s * 100), round($l * 100)];
        }

        return $this->_hsl;
    }

    /**
     * Returns the color’s red, green, and blue channels, converted to linear light values between 0 and 1.
     *
     * @return float[]
     */
    private function _linearRgb(): array
    {
        return array_map(function(int $channel) {
            $channel /= 255;
            return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, [$this->getRed(), $this->getGreen(), $this->getBlue()]);
    }

    /**
     * Returns the color’s relative luminance, per WCAG 2.
     *
     * @return float
     * @see https://www.w3.org/TR/WCAG22/#dfn-relative-luminance
     */
    private function _relativeLuminance(): float
    {
        [$r, $g, $b] = $this->_linearRgb();
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Returns the WCAG 2 contrast ratio between this color and another one, between 1 and 21.
     *
     * The ratio isn’t rounded, so it can be compared to WCAG thresholds (e.g. `4.5`) directly.
     * Alpha channels are ignored.
     *
     * @param ColorData|string $color The other color, as a `ColorData` object or hex value
     * @return float
     * @since 5.12.0
     * @see https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio
     */
    #[AllowedInSandbox]
    public function getContrastRatio(ColorData|string $color): float
    {
        if (is_string($color)) {
            $color = new ColorData(ColorValidator::normalizeColor($color));
        }

        $luminances = [$this->_relativeLuminance(), $color->_relativeLuminance()];
        return (max($luminances) + 0.05) / (min($luminances) + 0.05);
    }

    /**
     * Returns black (`#000000`) or white (`#ffffff`), whichever has a higher contrast ratio with this color.
     *
     * This is useful for choosing a text color to display over this color.
     *
     * @return string
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getContrastColor(): string
    {
        return $this->getContrastRatio('#000000') >= $this->getContrastRatio('#ffffff') ? '#000000' : '#ffffff';
    }

    /**
     * Get brightness of an image. Values closer to 0 are darker, closer to 1 are lighter.
     *
     * @see http://stackoverflow.com/a/12228906/1136822 Stack Overflow answer.
     * @see https://en.wikipedia.org/wiki/Luma_(video) Luma
     * @return float
     */
    #[AllowedInSandbox]
    public function getLuma(): float
    {
        return (0.2126 * $this->getRed() + 0.7152 * $this->getGreen() + 0.0722 * $this->getBlue()) / 255;
    }
}
