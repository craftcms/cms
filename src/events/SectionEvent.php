<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Section event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SectionEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\app\models\Section The section model associated with the event.
     */
    public $section;

    /**
     * @var boolean Whether the section is brand new
     */
    public $isNew = false;
}
