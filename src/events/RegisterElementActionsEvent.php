<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * RegisterElementActionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RegisterElementActionsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The selected source’s key, if any.
     */
    public $source;

    /**
     * @var array List of registered actions for the element type.
     */
    public $actions = [];
}
