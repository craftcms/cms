<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\image;

use Craft;
use craft\app\base\Image;
use craft\app\errors\ImageException;
use craft\app\helpers\Image as ImageHelper;
use craft\app\helpers\Io;

/**
 * Svg class is used for SVG file manipulations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Svg extends Image
{
    // Constants
    // =========================================================================

    const SVG_WIDTH_RE = '/(<svg[^>]*\swidth=")([\d\.]+)([a-z]*)"/si';
    const SVG_HEIGHT_RE = '/(<svg[^>]*\sheight=")([\d\.]+)([a-z]*)"/si';
    const SVG_VIEWBOX_RE = '/(<svg[^>]*\sviewBox=")(\d+(?:,|\s)\d+(?:,|\s)(\d+)(?:,|\s)(\d+))"/si';
    const SVG_ASPECT_RE = '/(<svg[^>]*\spreserveAspectRatio=")([a-z]+\s[a-z]+)"/si';
    const SVG_TAG_RE = '/<svg/si';
    const SVG_CLEANUP_WIDTH_RE = '/(<svg[^>]*\s)width="[\d\.]+%"/si';
    const SVG_CLEANUP_HEIGHT_RE = '/(<svg[^>]*\s)height="[\d\.]+%"/si';

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    private $_svgContent;

    /**
     * @var integer
     */
    private $_height;

    /**
     * @var integer
     */
    private $_width;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * @inheritdoc
     */
    public function getHeight()
    {
        return $this->_height;
    }

    /**
     * @inheritdoc
     */
    public function getExtension()
    {
        return "svg";
    }

    /**
     * @inheritdoc
     */
    public function loadImage($path)
    {
        if (!Io::fileExists($path)) {
            Craft::error('Tried to load an image at '.$path.', but the file does not exist.');
            throw new ImageException(Craft::t('app', 'No file exists at the given path.'));
        }

        list($width, $height) = ImageHelper::getImageSize($path);

        $svg = Io::getFileContents($path);

        // If the size is defined by viewbox only, add in width and height attributes
        if (!preg_match(static::SVG_WIDTH_RE,
                $svg) && preg_match(static::SVG_HEIGHT_RE, $svg)
        ) {
            $svg = preg_replace(static::SVG_TAG_RE,
                "<svg width=\"{$width}px\" height=\"{$height}px\" ", $svg);
        }

        $this->_height = $height;
        $this->_width = $width;

        $this->_svgContent = $svg;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function crop($x1, $x2, $y1, $y2)
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        // If the SVG had a viewbox, it might have been scaled already.
        if (preg_match(static::SVG_VIEWBOX_RE, $this->_svgContent,
            $viewboxMatch)) {
            $viewBoxXFactor = $this->getWidth() / round($viewboxMatch[3]);
            $viewBoxYFactor = $this->getHeight() / round($viewboxMatch[4]);
        } else {
            $viewBoxXFactor = 1;
            $viewBoxYFactor = 1;
        }


        $this->resize($width, $height);

        $x1 = $x1 / $viewBoxXFactor;
        $y1 = $y1 / $viewBoxYFactor;
        $width = $width / $viewBoxXFactor;
        $height = $height / $viewBoxYFactor;

        $value = "{$x1} {$y1} {$width} {$height}";

        // Add/modify the viewbox to crop the image.
        if (preg_match(static::SVG_VIEWBOX_RE, $this->_svgContent)) {
            $this->_svgContent = preg_replace(static::SVG_VIEWBOX_RE,
                "\${1}{$value}\"", $this->_svgContent);
        } else {
            $this->_svgContent = preg_replace(static::SVG_TAG_RE,
                "<svg viewBox=\"{$value}\"", $this->_svgContent);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function scaleToFit($targetWidth, $targetHeight = null, $scaleIfSmaller = true)
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
    public function scaleAndCrop($targetWidth, $targetHeight = null, $scaleIfSmaller = true, $cropPositions = 'center-center')
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            // Scale first.
            $this->resize($targetWidth, $targetHeight);

            // Reverse the components
            $cropPositions = join("-",
                array_reverse(explode("-", $cropPositions)));

            $value = "x".strtr($cropPositions, [
                    'left' => 'Min',
                    'center' => 'Mid',
                    'right' => 'Max',
                    'top' => 'Min',
                    'bottom' => 'Max',
                    '-' => 'Y'
                ])." slice";

            // Add/modify aspect ratio information
            if (preg_match(static::SVG_ASPECT_RE, $this->_svgContent)) {
                $this->_svgContent = preg_replace(static::SVG_ASPECT_RE,
                    "\${1}{$value}\"", $this->_svgContent);
            } else {
                $this->_svgContent = preg_replace(static::SVG_TAG_RE,
                    "<svg preserveAspectRatio=\"{$value}\"",
                    $this->_svgContent);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function resize($targetWidth, $targetHeight = null)
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if (preg_match(static::SVG_WIDTH_RE, $this->_svgContent) && preg_match(static::SVG_HEIGHT_RE, $this->_svgContent)
        ) {
            $this->_svgContent = preg_replace(static::SVG_WIDTH_RE, "\${1}{$targetWidth}px\"", $this->_svgContent);
            $this->_svgContent = preg_replace(static::SVG_HEIGHT_RE, "\${1}{$targetHeight}px\"", $this->_svgContent);
        } else {
            // In case the root element has dimension attributes set with percentage,
            // weed them out so we don't duplicate them.
            $this->_svgContent = preg_replace(static::SVG_CLEANUP_WIDTH_RE, "\${1}", $this->_svgContent);
            $this->_svgContent = preg_replace(static::SVG_CLEANUP_HEIGHT_RE, "\${1}", $this->_svgContent);

            $this->_svgContent = preg_replace(static::SVG_TAG_RE, "<svg width=\"{$targetWidth}px\" height=\"{$targetHeight}px\"", $this->_svgContent);
        }

        $this->_width = $targetWidth;
        $this->_height = $targetHeight;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function saveAs($targetPath, $autoQuality = false)
    {
        if (Io::getExtension($targetPath) == 'svg') {
            Io::writeToFile($targetPath, $this->_svgContent);
        } else {
            throw new ImageException(Craft::t('app',
                'Manipulated SVG image rasterizing is unreliable. See \craft\app\services\Images::loadImage()'));
        }

        return true;
    }

    /**
     * Returns the SVG string.
     *
     * @return string
     */
    public function getSvgString()
    {
        return $this->_svgContent;
    }

    /**
     * @inheritdoc
     */
    public function getIsTransparent()
    {
        return true;
    }
}
