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
     * FieldLayoutProviderInterface defines the common interface to be implemented by classes
     * which provide a field layout.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.5.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface} instead.
     */
    interface FieldLayoutProviderInterface extends \CraftCms\Cms\Component\Contracts\Grippable
    {
    }
}

class_alias(\CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface::class, FieldLayoutProviderInterface::class);
