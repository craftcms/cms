<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\image;

use Craft;
use craft\base\Image;
use craft\errors\ImageException;
use craft\helpers\FileHelper;
use craft\helpers\Image as ImageHelper;

/**
 * Svg class is used for SVG file manipulations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Svg extends Image
{
    // Constants
    // =========================================================================

    const SVG_WIDTH_RE = '/(<svg[^>]*\swidth=")([\d\.]+)([a-z]*)"/i';
    const SVG_HEIGHT_RE = '/(<svg[^>]*\sheight=")([\d\.]+)([a-z]*)"/i';
    const SVG_VIEWBOX_RE = '/(<svg[^>]*\sviewBox=")(-?[\d.]+(?:,|\s)-?[\d.]+(?:,|\s)-?([\d.]+)(?:,|\s)(-?[\d.]+))"/i';
    const SVG_ASPECT_RE = '/(<svg[^>]*\spreserveAspectRatio=")([a-z]+\s[a-z]+)"/i';
    const SVG_TAG_RE = '/<svg/i';
    const SVG_CLEANUP_WIDTH_RE = '/(<svg[^>]*\s)width="[\d\.]+%"/i';
    const SVG_CLEANUP_HEIGHT_RE = '/(<svg[^>]*\s)height="[\d\.]+%"/i';

    // Properties
    // =========================================================================

    /**
     * @var string|null
     */
    private $_svgContent;

    /**
     * @var int|null
     */
    private $_height;

    /**
     * @var int|null
     */
    private $_width;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getWidth(): int
    {
        return $this->_width;
    }

    /**
     * @inheritdoc
     */
    public function getHeight(): int
    {
        return $this->_height;
    }

    /**
     * @inheritdoc
     */
    public function getExtension(): string
    {
        return 'svg';
    }

    /**
     * @inheritdoc
     */
    public function loadImage(string $path)
    {
        if (!is_file($path)) {
            Craft::error('Tried to load an image at ' . $path . ', but the file does not exist.', __METHOD__);
            throw new ImageException(Craft::t('app', 'No file exists at the given path.'));
        }

        list($width, $height) = ImageHelper::imageSize($path);

        $svg = file_get_contents($path);

        if ($svg === false) {
            Craft::error('Tried to read the SVG contents at ' . $path . ', but could not.', __METHOD__);
            throw new ImageException(Craft::t('app', 'Could not read SVG contents.'));
        }

        // If the size is defined by viewbox only, add in width and height attributes
        if (!preg_match(self::SVG_WIDTH_RE, $svg) && preg_match(self::SVG_HEIGHT_RE, $svg)) {
            $svg = preg_replace(self::SVG_TAG_RE,
                "<svg width=\"{$width}px\" height=\"{$height}px\" ", $svg);
        }

        $this->_height = (int)$height;
        $this->_width = (int)$width;

        $this->_svgContent = $svg;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function crop(int $x1, int $x2, int $y1, int $y2)
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        // If the SVG had a viewbox, it might have been scaled already.
        if (preg_match(self::SVG_VIEWBOX_RE, $this->_svgContent,
            $viewboxMatch)) {
            $viewBoxXFactor = $this->getWidth() / round($viewboxMatch[3]);
            $viewBoxYFactor = $this->getHeight() / round($viewboxMatch[4]);
        } else {
            $viewBoxXFactor = 1;
            $viewBoxYFactor = 1;
        }


        $this->resize($width, $height);

        $x1 /= $viewBoxXFactor;
        $y1 /= $viewBoxYFactor;
        $width /= $viewBoxXFactor;
        $height /= $viewBoxYFactor;

        $value = "{$x1} {$y1} {$width} {$height}";

        // Add/modify the viewbox to crop the image.
        if (preg_match(self::SVG_VIEWBOX_RE, $this->_svgContent)) {
            $this->_svgContent = preg_replace(self::SVG_VIEWBOX_RE,
                "\${1}{$value}\"", $this->_svgContent);
        } else {
            $this->_svgContent = preg_replace(self::SVG_TAG_RE,
                "<svg viewBox=\"{$value}\"", $this->_svgContent);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function scaleToFit(int $targetWidth, int $targetHeight = null, bool $scaleIfSmaller = true)
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            $factor = max($this->getWidth() / $targetWidth,
                $this->getHeight() / $targetHeight);
            $this->resize(round($this->getWidth() / $factor),
                round($this->getHeight() / $factor));
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function scaleAndCrop(int $targetWidth = null, int $targetHeight = null, bool $scaleIfSmaller = true, $cropPosition = 'center-center')
    {
        // TODO If we encounter a focal point, rasterize and crop with focal.
        if (is_array($cropPosition)) {
            throw new ImageException(Craft::t('app', 'Currently SVG images do not support focal point.'));
        }

        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            // Scale first.
            $this->resize($targetWidth, $targetHeight);

            // Reverse the components
            $cropPositions = implode('-', array_reverse(explode('-', $cropPosition)));

            $value = 'x' . strtr($cropPositions, [
                    'left' => 'Min',
                    'center' => 'Mid',
                    'right' => 'Max',
                    'top' => 'Min',
                    'bottom' => 'Max',
                    '-' => 'Y'
                ]) . ' slice';

            // Add/modify aspect ratio information
            if (preg_match(self::SVG_ASPECT_RE, $this->_svgContent)) {
                $this->_svgContent = preg_replace(self::SVG_ASPECT_RE,
                    "\${1}{$value}\"", $this->_svgContent);
            } else {
                $this->_svgContent = preg_replace(self::SVG_TAG_RE,
                    "<svg preserveAspectRatio=\"{$value}\"",
                    $this->_svgContent);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function resize(int $targetWidth, int $targetHeight = null)
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if (preg_match(self::SVG_WIDTH_RE, $this->_svgContent) && preg_match(self::SVG_HEIGHT_RE, $this->_svgContent)
        ) {
            $this->_svgContent = preg_replace(self::SVG_WIDTH_RE, "\${1}{$targetWidth}px\"", $this->_svgContent);
            $this->_svgContent = preg_replace(self::SVG_HEIGHT_RE, "\${1}{$targetHeight}px\"", $this->_svgContent);
        } else {
            // In case the root element has dimension attributes set with percentage,
            // weed them out so we don't duplicate them.
            $this->_svgContent = preg_replace(self::SVG_CLEANUP_WIDTH_RE, '${1}', $this->_svgContent);
            $this->_svgContent = preg_replace(self::SVG_CLEANUP_HEIGHT_RE, '${1}', $this->_svgContent);

            $this->_svgContent = preg_replace(self::SVG_TAG_RE, "<svg width=\"{$targetWidth}px\" height=\"{$targetHeight}px\"", $this->_svgContent);
        }

        // If viewbox does not exist, add it to retain the scale.
        if (!preg_match(static::SVG_VIEWBOX_RE, $this->_svgContent)) {
            $viewBox = "0 0 {$this->_width} {$this->_height}";
            $this->_svgContent = preg_replace(static::SVG_TAG_RE, "<svg viewBox=\"{$viewBox}\"", $this->_svgContent);
        }

        $this->_width = $targetWidth;
        $this->_height = $targetHeight;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function saveAs(string $targetPath, bool $autoQuality = false): bool
    {
        if (pathinfo($targetPath, PATHINFO_EXTENSION) === 'svg') {
            FileHelper::writeToFile($targetPath, $this->_svgContent);
        } else {
            throw new ImageException(Craft::t('app',
                'Manipulated SVG image rasterizing is unreliable. See \craft\services\Images::loadImage()'));
        }

        return true;
    }

    /**
     * Returns the SVG string.
     *
     * @return string
     */
    public function getSvgString(): string
    {
        return $this->_svgContent;
    }

    /**
     * @inheritdoc
     */
    public function getIsTransparent(): bool
    {
        return true;
    }
}
