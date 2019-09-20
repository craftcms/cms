<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Merged elements event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MergeElementsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The ID of the element that just got merged into the other.
     */
    public $mergedElementId;

    /**
     * @var int|null The ID of the element that prevailed in the merge.
     */
    public $prevailingElementId;
}
