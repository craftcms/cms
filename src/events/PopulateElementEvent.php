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
 * @since 3.0
 */
class PopulateElementEvent extends ElementEvent
{
    // Properties
    // =========================================================================

    /**
     * @var array|null The element query’s result for this element.
     */
    public $row;
}
