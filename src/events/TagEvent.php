<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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

    /**
     * @var boolean Whether the tag is brand new
     */
    public $isNew = false;
}
