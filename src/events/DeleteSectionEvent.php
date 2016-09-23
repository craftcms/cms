<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Delete section event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteSectionEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\app\models\Section The section model associated with the event.
     */
    public $section;
}
