<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Queries\Events\ElementHydrating} or {@see \CraftCms\Cms\Element\Queries\Events\ElementHydrated} instead.
 */
class PopulateElementEvent extends ElementEvent
{
    /**
     * @var array The element query’s result for this element.
     */
    public array $row;
}
