<?php

namespace craft\base\imagetransforms;

use CraftCms\Cms\Asset\Elements\Asset;

interface ImageEditorTransformerInterface
{
    public function startImageEditing(Asset $asset): void;

    public function flipImage(bool $flipX, bool $flipY): void;

    public function scaleImage(int $width, int $height): void;

    public function rotateImage(float $degrees): void;

    public function getEditedImageWidth(): int;

    public function getEditedImageHeight(): int;

    public function crop(int $x, int $y, int $width, int $height): void;

    public function finishImageEditing(): string;

    public function cancelImageEditing(): string;
}
