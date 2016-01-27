<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\Tag;

/**
 * Tag event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class TagEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Tag The tag model associated with the event.
     */
    public $tag;
}
