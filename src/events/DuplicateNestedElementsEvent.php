<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;

/**
 * Duplicate nested elements event
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class DuplicateNestedElementsEvent extends Event
{
    /**
     * @var ElementInterface The source element that nested elements were duplicated from
     */
    public ElementInterface $source;

    /**
     * @var ElementInterface The target element that nested elements were duplicated to
     */
    public ElementInterface $target;

    /**
     * @var array The new nested element IDs, indexed by their original elements’ IDs
     */
    public array $newElementIds;
}
