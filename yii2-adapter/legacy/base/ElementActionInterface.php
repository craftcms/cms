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
     * ElementActionInterface defines the common interface to be implemented by element action classes.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Element\Contracts\ElementActionInterface} instead.
     */
    interface ElementActionInterface extends \CraftCms\Cms\Element\Contracts\ElementActionInterface
    {
    }
}

class_alias(\CraftCms\Cms\Element\Contracts\ElementActionInterface::class, ElementActionInterface::class);
