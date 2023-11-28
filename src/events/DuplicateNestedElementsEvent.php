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
     * @var array Map of old element IDs and the new (duplicate) element IDs
     */
    public array $elementIds;

    /**
     * @var ElementInterface Owner element from which the duplication started
     */
    public ElementInterface $source;

    /**
     * @var ElementInterface Owner element to which the duplication was made
     */
    public ElementInterface $target;
}
