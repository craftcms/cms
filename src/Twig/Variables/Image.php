<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use craft\helpers\Image as ImageHelper;
use CraftCms\Cms\Support\Html;
use Symfony\Component\Mime\MimeTypes;

final class Image
{
    /**
     * @var array{int,int}|null
     */
    private ?array $size = null;

    public function __construct(
        private readonly string $path = '',
        private readonly string $url = ''
    ) {}

    /**
     * Returns an array of the width and height of the image.
     *
     * @return array{int,int}
     */
    public function getSize(): array
    {
        $this->size ??= ImageHelper::imageSize($this->path);

        return $this->size;
    }

    public function getWidth(): int
    {
        return $this->getSize()[0];
    }

    public function getHeight(): int
    {
        return $this->getSize()[1];
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMimeType(): ?string
    {
        return MimeTypes::getDefault()->guessMimeType($this->path);
    }

    public function getContents(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Returns a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the image.
     */
    public function getDataUrl(): string
    {
        return Html::dataUrlFromString($this->getContents(), $this->getMimeType());
    }
}
