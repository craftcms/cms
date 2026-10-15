<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Component\Component;

/**
 * @since 6.0.0
 */
abstract class Image extends Component
{
    /** @var callable|null */
    private $heartBeatCallback;

    abstract public function getWidth(): int;

    abstract public function getHeight(): int;

    abstract public function getExtension(): string;

    abstract public function loadImage(string $path): self;

    abstract public function crop(int $x1, int $x2, int $y1, int $y2): self;

    abstract public function scaleToFit(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true): self;

    /** @param array{x:numeric,y:numeric}|string $cropPosition */
    abstract public function scaleAndCrop(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true, array|string $cropPosition = 'center-center'): self;

    abstract public function resize(?int $targetWidth, ?int $targetHeight): self;

    abstract public function saveAs(string $targetPath, bool $autoQuality = false): bool;

    abstract public function getIsTransparent(): bool;

    /**
     * @param-out int $width
     * @param-out int $height
     */
    protected function normalizeDimensions(int|string|null &$width, int|string|null &$height): void
    {
        $targetWidth = $width;
        $targetHeight = $height;

        if (preg_match('/^([\d]+|AUTO)x([\d]+|AUTO)/', (string) $targetWidth, $matches)) {
            $targetWidth = $matches[1] !== 'AUTO' ? (int) $matches[1] : null;
            $targetHeight = $matches[2] !== 'AUTO' ? (int) $matches[2] : null;
        }

        $targetWidth = $targetWidth === null ? null : (int) $targetWidth;
        $targetHeight = $targetHeight === null ? null : (int) $targetHeight;

        [$width, $height] = ImageHelper::calculateMissingDimension($targetWidth, $targetHeight, $this->getWidth(), $this->getHeight());
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
