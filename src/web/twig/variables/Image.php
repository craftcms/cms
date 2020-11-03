<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

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
    protected $path;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var array|null
     */
    protected $size;

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
        if ($this->size !== null) {
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
}
