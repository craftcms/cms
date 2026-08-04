<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Component\Component;

abstract class Image extends Component
{
    private $heartBeatCallback;

    abstract public function getWidth(): int;

    abstract public function getHeight(): int;

    abstract public function getExtension(): string;

    abstract public function loadImage(string $path): self;

    abstract public function crop(int $x1, int $x2, int $y1, int $y2): self;

    abstract public function scaleToFit(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true): self;

    abstract public function scaleAndCrop(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true, array|string $cropPosition = 'center-center'): self;

    abstract public function resize(?int $targetWidth, ?int $targetHeight): self;

    abstract public function saveAs(string $targetPath, bool $autoQuality = false): bool;

    abstract public function getIsTransparent(): bool;

    /**
     * @param-out int|string $width
     * @param-out int|string $height
     */
    protected function normalizeDimensions(int|string|null &$width, int|string|null &$height): void
    {
        if (preg_match('/^([\d]+|AUTO)x([\d]+|AUTO)/', (string) $width, $matches)) {
            $width = $matches[1] !== 'AUTO' ? (int) $matches[1] : '';
            $height = $matches[2] !== 'AUTO' ? (int) $matches[2] : '';
        }

        if (! $height || ! $width) {
            [$width, $height] = ImageHelper::calculateMissingDimension($width, $height, $this->getWidth(), $this->getHeight());
        }
    }

    public function setHeartbeatCallback(?callable $method = null): void
    {
        $this->heartBeatCallback = $method;
    }

    public function heartbeat(): void
    {
        if (is_callable($this->heartBeatCallback)) {
            call_user_func($this->heartBeatCallback);
        }
    }
}
