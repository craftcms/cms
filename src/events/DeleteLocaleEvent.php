<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Delete locale event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteLocaleEvent extends LocaleEvent
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The locale ID that the old locale's exclusive content should be transferred to.
     */
    public $transferContentTo;
}
