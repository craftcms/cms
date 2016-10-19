<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * ElementStructureEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementStructureEvent extends ModelEvent
{
    // Properties
    // =========================================================================

    /**
     * @var integer The structure ID
     */
    public $structureId;
}
