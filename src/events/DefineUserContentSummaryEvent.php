<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineUserContentSummaryEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DefineUserContentSummaryEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var int|int[] The user ID(s) associated with the event
     */
    public $userId;

    /**
     * @var string[] Summary of content that is owned by the user(s)
     */
    public $contentSummary = [];
}
