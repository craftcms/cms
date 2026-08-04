<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * PopulateElementsEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.1.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Queries\Events\ElementsHydrated} instead.
 */
class PopulateElementsEvent extends Event
{
    /**
     * @var ElementInterface[] The populated elements
     */
    public array $elements;

    /**
     * @var array[] The element query’s raw result data
     */
    public array $rows;
}
