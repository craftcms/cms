<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Locale delete event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class LocaleDeleteEvent extends LocaleEvent
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The locale ID that the old locale's exclusive content should be transferred to.
     */
    public $transferContentTo;
}
