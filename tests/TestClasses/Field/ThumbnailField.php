<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Field;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Image\Enums\ImageTransformMode;

class ThumbnailField extends PlainText implements ThumbableFieldInterface
{
    public static array $requests = [];

    public function getThumbHtml(mixed $value, ElementInterface $element, int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string
    {
        self::$requests[] = [$value, $element, $size, $mode];

        return $value;
    }
}
