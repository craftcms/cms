<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.6.0
     * @deprecated 6.0.0
     */
    interface CrossSiteCopyableFieldInterface
    {
    }
}

class_alias(\CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface::class, CrossSiteCopyableFieldInterface::class);
