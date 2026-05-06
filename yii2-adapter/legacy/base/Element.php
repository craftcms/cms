<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Element} instead.
     */
    abstract class Element extends \CraftCms\Cms\Element\Element
    {
    }
}

class_alias(\CraftCms\Cms\Element\Element::class, Element::class);
