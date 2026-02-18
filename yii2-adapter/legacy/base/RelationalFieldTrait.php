<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Field\Concerns\RelationalField;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Concerns\RelationalField} instead.
     * @phpstan-ignore-next-line
     */
    trait RelationalFieldTrait
    {
    }
}

class_alias(RelationalField::class, RelationalFieldTrait::class);
