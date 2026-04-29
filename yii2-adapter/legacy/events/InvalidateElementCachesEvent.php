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
 * InvalidateElementCachesEvent class.
 *
 * The event that is triggered when element TagDependency caches are invalidated
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Events\InvalidateElementCaches} instead.
 */
class InvalidateElementCachesEvent extends Event
{
    /**
     * @var array An array of TagDependency tag names that are being invalidated
     */
    public array $tags;

    /**
     * @var ElementInterface|null The element whose caches are being invalidated, if this was triggered from
     * [[\craft\services\Elements::invalidateCachesForElement()]].
     * @since 5.2.0
     */
    public ?ElementInterface $element = null;
}
