<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Image\Enums\ImageTransformMode;

/**
 * ThumbableFieldInterface defines the common interface to be implemented by field classes
 * that can provide a thumbnail for element card views.
 */
interface ThumbableFieldInterface extends FieldInterface
{
    /**
     * Returns the HTML for an element’s thumbnail.
     *
     * @param  mixed  $value  The field’s value
     * @param  ElementInterface  $element  The element the field is associated with
     * @param  int  $size  The maximum width and height the thumbnail should have.
     * @param  ImageTransformMode  $mode  How the image should fit within the thumbnail bounds.
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string;
}
