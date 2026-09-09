<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Support\Html;

class ModeThumbnailField extends PlainText implements ThumbableFieldInterface
{
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string
    {
        return $value === $mode->value ? Html::tag('craft-thumbnail', '', [
            'mode' => $mode->value,
            'sizes' => "calc({$size}rem/16)",
        ]) : null;
    }
}
