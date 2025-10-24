<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use craft\base\Serializable;

/**
 * @property string $hex
 * @property string $rgb
 * @property int $red
 * @property int $green
 * @property int $blue
 * @property int $r
 * @property int $g
 * @property int $b
 * @property float $luma
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Top Shelf Craft <michael@michaelrog.com>
 */
final class ColorData implements \Stringable, Serializable
{
    /**
     * @see _hsl()
     */
    private array $_hsl;

    /**
     * Constructor.
     *
     * @param  string  $_hex  hex color value, beginning with `#`. (Shorthand is not supported, e.g. `#f00`.)
     * @param  string|null  $label  The human-facing label for the color.
     */
    public function __construct(private readonly string $_hex, public ?string $label = null) {}

    public function __toString(): string
    {
        return $this->_hex;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): mixed
    {
        return $this->_hex;
    }

    /**
     * Returns the color as a hex.
     */
    public function getHex(): string
    {
        return $this->_hex;
    }

    /**
     * Returns the color in `rgb()` syntax.
     */
    public function getRgb(): string
    {
        return "rgb({$this->getRed()},{$this->getGreen()},{$this->getBlue()})";
    }

    /**
     * Returns the color in `hsl()` syntax.
     */
    public function getHsl(): string
    {
        [$h, $s, $l] = $this->_hsl();

        return "hsl($h,$s%,$l%)";
    }

    public function getRed(): int
    {
        return hexdec(substr($this->_hex, 1, 2));
    }

    public function getR(): int
    {
        return $this->getRed();
    }

    public function getGreen(): int
    {
        return hexdec(substr($this->_hex, 3, 2));
    }

    public function getG(): int
    {
        return $this->getGreen();
    }

    public function getBlue(): int
    {
        return hexdec(substr($this->_hex, 5, 2));
    }

    public function getB(): int
    {
        return $this->getBlue();
    }

    public function getHue(): int
    {
        return $this->_hsl()[0];
    }

    public function getH(): int
    {
        return $this->getHue();
    }

    public function getSaturation(): int
    {
        return $this->_hsl()[1];
    }

    public function getS(): int
    {
        return $this->getSaturation();
    }

    public function getLightness(): int
    {
        return $this->_hsl()[2];
    }

    public function getL(): int
    {
        return $this->getLightness();
    }

    private function _hsl(): array
    {
        if (isset($this->_hsl)) {
            return $this->_hsl;
        }

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

        return $this->_hsl = [round($h), round($s * 100), round($l * 100)];
    }

    /**
     * Get brightness of an image. Values closer to 0 are darker, closer to 1 are lighter.
     *
     * @see http://stackoverflow.com/a/12228906/1136822 Stack Overflow answer.
     * @see https://en.wikipedia.org/wiki/Luma_(video) Luma
     */
    public function getLuma(): float
    {
        return (0.2126 * $this->getRed() + 0.7152 * $this->getGreen() + 0.0722 * $this->getBlue()) / 255;
    }
}
