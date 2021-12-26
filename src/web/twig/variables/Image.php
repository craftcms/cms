<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image as ImageHelper;

/**
 * Class Image variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Image
{
    /**
     * @var string|null
     */
    protected ?string $path = null;

    /**
     * @var string|null
     */
    protected ?string $url = null;

    /**
     * @var array|null
     */
    protected ?array $size = null;

    /**
     * Constructor
     *
     * @param string $path
     * @param string $url
     */
    public function __construct(string $path, string $url = '')
    {
        $this->path = $path;
        $this->url = $url;
    }

    /**
     * Returns an array of the width and height of the image.
     *
     * @return array
     */
    public function getSize(): array
    {
        if (isset($this->size)) {
            return $this->size;
        }

        return $this->size = ImageHelper::imageSize($this->path);
    }

    /**
     * Returns the image's width.
     *
     * @return int
     */
    public function getWidth(): int
    {
        $size = $this->getSize();

        return $size[0];
    }

    /**
     * Returns the image's height.
     *
     * @return int
     */
    public function getHeight(): int
    {
        $size = $this->getSize();

        return $size[1];
    }

    /**
     * Returns the image's URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Returns the image’s path.
     *
     * @return string
     * @since 3.7.27
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the file’s MIME type, if it can be determined.
     *
     * @return string|null
     * @since 3.7.27
     */
    public function getMimeType(): ?string
    {
        return FileHelper::getMimeTypeByExtension($this->path);
    }

    /**
     * Returns the file’s contents.
     *
     * @return string
     * @since 3.7.27
     */
    public function getContents(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Returns a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the image.
     *
     * @return string
     * @since 3.7.27
     */
    public function getDataUrl(): string
    {
        return Html::dataUrlFromString($this->getContents(), $this->getMimeType());
    }
}
