<?php

namespace craft\base\imagetransforms;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Contracts\EagerImageTransformerInterface} instead.
     */
    interface EagerImageTransformerInterface extends \CraftCms\Cms\Image\Contracts\EagerImageTransformerInterface
    {
    }
}

class_alias(\CraftCms\Cms\Image\Contracts\EagerImageTransformerInterface::class, EagerImageTransformerInterface::class);
