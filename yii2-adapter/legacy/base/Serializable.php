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
     * Serializable is the interface that should be implemented by classes who want to support customizable representation of their instances
     * when getting stored somewhere.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Shared\Contracts\Serializable} instead.
     */
    interface Serializable extends \CraftCms\Cms\Shared\Contracts\Serializable
    {
    }
}

class_alias(\CraftCms\Cms\Shared\Contracts\Serializable::class, Serializable::class);
