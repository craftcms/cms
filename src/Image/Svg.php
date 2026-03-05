<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use craft\helpers\FileHelper;
use CraftCms\Cms\Asset\Exceptions\ImageException;
use Illuminate\Support\Facades\Log;

use function CraftCms\Cms\t;

class Svg extends Image
{
    public const SVG_WIDTH_RE = '/(<svg[^>]*\swidth=")([\d\.]+)([a-z]*)"/i';

    public const SVG_HEIGHT_RE = '/(<svg[^>]*\sheight=")([\d\.]+)([a-z]*)"/i';

    public const SVG_VIEWBOX_RE = '/(<svg[^>]*\sviewBox=")(-?[\d.]+(?:,|\s)-?[\d.]+(?:,|\s)-?([\d.]+)(?:,|\s)(-?[\d.]+))"/i';

    public const SVG_ASPECT_RE = '/(<svg[^>]*\spreserveAspectRatio=")([a-z]+(?:\s[a-z]+)?)"/i';

    public const SVG_TAG_RE = '/<svg/i';

    public const SVG_CLEANUP_WIDTH_RE = '/(<svg[^>]*\s)width="[\d\.]+%"/i';

    public const SVG_CLEANUP_HEIGHT_RE = '/(<svg[^>]*\s)height="[\d\.]+%"/i';

    private ?string $_svgContent = null;

    private ?int $_height = null;

    private ?int $_width = null;

    public function getWidth(): int
    {
        return $this->_width;
    }

    public function getHeight(): int
    {
        return $this->_height;
    }

    public function getExtension(): string
    {
        return 'svg';
    }

    public function loadImage(string $path): self
    {
        if (! is_file($path)) {
            Log::error('Tried to load an image at '.$path.', but the file does not exist.', [__METHOD__]);
            throw new ImageException(t('No file exists at the given path.'));
        }

        $svg = file_get_contents($path);

        if ($svg === false) {
            Log::error('Tried to read the SVG contents at '.$path.', but could not.', [__METHOD__]);
            throw new ImageException(t('Could not read SVG contents.'));
        }

        [$width, $height] = ImageHelper::parseSvgSize($svg);

        // If the size is defined by viewbox only, add in width and height attributes
        if (! preg_match(self::SVG_WIDTH_RE, $svg) && ! preg_match(self::SVG_HEIGHT_RE, $svg)) {
            $svg = preg_replace(self::SVG_TAG_RE,
                "<svg width=\"{$width}px\" height=\"{$height}px\" ", $svg);
        }

        $this->_height = $height;
        $this->_width = $width;

        $this->_svgContent = $svg;

        return $this;
    }

    public function crop(int $x1, int $x2, int $y1, int $y2): self
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        // If the SVG had a viewbox, it might have been scaled already.
        if (preg_match(self::SVG_VIEWBOX_RE, (string) $this->_svgContent,
            $viewboxMatch)) {
            $viewBoxXFactor = $this->getWidth() / round((float) $viewboxMatch[3]);
            $viewBoxYFactor = $this->getHeight() / round((float) $viewboxMatch[4]);
        } else {
            $viewBoxXFactor = 1;
            $viewBoxYFactor = 1;
        }

        $this->resize($width, $height);

        $x1 /= $viewBoxXFactor;
        $y1 /= $viewBoxYFactor;
        $width /= $viewBoxXFactor;
        $height /= $viewBoxYFactor;

        $value = "$x1 $y1 $width $height";

        // Add/modify the viewbox to crop the image.
        if (preg_match(self::SVG_VIEWBOX_RE, (string) $this->_svgContent)) {
            $this->_svgContent = preg_replace(self::SVG_VIEWBOX_RE,
                "\${1}$value\"", (string) $this->_svgContent);
        } else {
            $this->_svgContent = preg_replace(self::SVG_TAG_RE,
                "<svg viewBox=\"$value\"", (string) $this->_svgContent);
        }

        return $this;
    }

    public function scaleToFit(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true): self
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            $factor = max($this->getWidth() / $targetWidth,
                $this->getHeight() / $targetHeight);
            $this->resize((int) round($this->getWidth() / $factor), (int) round($this->getHeight() / $factor));
        }

        return $this;
    }

    public function scaleAndCrop(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true, array|string $cropPosition = 'center-center'): self
    {
        // SVGs don’t support focal points yet
        if (is_array($cropPosition)) {
            $cropPosition = 'center-center';
        }

        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            // Scale first.
            $this->resize($targetWidth, $targetHeight);

            // Reverse the components
            $cropPositions = implode('-', array_reverse(explode('-', $cropPosition)));

            $value = 'x'.strtr($cropPositions, [
                'left' => 'Min',
                'center' => 'Mid',
                'right' => 'Max',
                'top' => 'Min',
                'bottom' => 'Max',
                '-' => 'Y',
            ]).' slice';

            // Add/modify aspect ratio information
            if (preg_match(self::SVG_ASPECT_RE, (string) $this->_svgContent)) {
                $this->_svgContent = preg_replace(self::SVG_ASPECT_RE,
                    "\${1}$value\"", (string) $this->_svgContent);
            } else {
                $this->_svgContent = preg_replace(self::SVG_TAG_RE,
                    "<svg preserveAspectRatio=\"$value\"",
                    (string) $this->_svgContent);
            }
        }

        return $this;
    }

    public function resize(?int $targetWidth, ?int $targetHeight): self
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if (preg_match(self::SVG_WIDTH_RE, (string) $this->_svgContent) && preg_match(self::SVG_HEIGHT_RE, (string) $this->_svgContent)) {
            $this->_svgContent = preg_replace(self::SVG_WIDTH_RE, "\${1}{$targetWidth}px\"", (string) $this->_svgContent);
            $this->_svgContent = preg_replace(self::SVG_HEIGHT_RE, "\${1}{$targetHeight}px\"", (string) $this->_svgContent);
        } else {
            // In case the root element has dimension attributes set with percentage,
            // weed them out so we don't duplicate them.
            $this->_svgContent = preg_replace(self::SVG_CLEANUP_WIDTH_RE, '${1}', (string) $this->_svgContent);
            $this->_svgContent = preg_replace(self::SVG_CLEANUP_HEIGHT_RE, '${1}', (string) $this->_svgContent);

            $this->_svgContent = preg_replace(self::SVG_TAG_RE, "<svg width=\"{$targetWidth}px\" height=\"{$targetHeight}px\"", (string) $this->_svgContent);
        }

        // If viewbox does not exist, add it to retain the scale.
        if (! preg_match(static::SVG_VIEWBOX_RE, (string) $this->_svgContent)) {
            $viewBox = "0 0 $this->_width $this->_height";
            $this->_svgContent = preg_replace(static::SVG_TAG_RE, "<svg viewBox=\"$viewBox\"", (string) $this->_svgContent);
        }

        $this->_width = $targetWidth;
        $this->_height = $targetHeight;

        return $this;
    }

    public function saveAs(string $targetPath, bool $autoQuality = false): bool
    {
        if (pathinfo($targetPath, PATHINFO_EXTENSION) === 'svg') {
            FileHelper::writeToFile($targetPath, $this->_svgContent);
        } else {
            throw new ImageException(t('Manipulated SVG image rasterizing is unreliable. See \CraftCms\Cms\Image\Images::loadImage()'));
        }

        return true;
    }

    public function getSvgString(): string
    {
        return $this->_svgContent;
    }

    public function getIsTransparent(): bool
    {
        return true;
    }
}
