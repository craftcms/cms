<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

/**
 * Element query event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Events\ElementResavings}, {@see \CraftCms\Cms\Element\Events\ElementResaveds}, {@see \CraftCms\Cms\Element\Events\ElementPropagatings}, or {@see \CraftCms\Cms\Element\Events\ElementLifecyclePropagatedElements} instead.
 */
class ElementQueryEvent extends Event
{
    /**
     * @var ElementQueryInterface The element query.
     */
    public ElementQueryInterface $query;
}
