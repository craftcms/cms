<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Merged elements event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MergeElementsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var int The ID of the element that just got merged into the other.
     */
    public $mergedElementId;

    /**
     * @var int The ID of the element that prevailed in the merge.
     */
    public $prevailingElementId;
}
