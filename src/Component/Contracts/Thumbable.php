<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;

/**
 *  Thumbable defines the common interface to be implemented by components that
 *  can have thumbnails within the control panel.
 */
interface Thumbable
{
    /**
     * Returns the HTML for the component’s thumbnail, if it has one.
     *
     * @param  int  $size  The maximum width and height the thumbnail should have.
     * @param  ImageTransformMode  $mode  How the image should fit within the thumbnail bounds.
     */
    #[AllowedInSandbox]
    public function getThumbHtml(int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string;
}
