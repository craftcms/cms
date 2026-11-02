<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use craft\base\Model;
use craft\base\Serializable;
use craft\web\twig\AllowedInSandbox;

/**
 * Multi-select option field data class.
 *
 * @property string $hex
 * @property string $rgb
 * @property string $rgba
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
