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
 */
class PopulateElementEvent extends ElementEvent
{
    /**
     * @var array The element query’s result for this element.
     */
    public array $row;

    /**
     * @var array The element’s field values, indexed by their layout element UUIDs
     * @since 5.10.0
     */
    public array $content;
}
