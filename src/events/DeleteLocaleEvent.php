<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Delete locale event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteLocaleEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The locale ID that is getting deleted.
     */
    public $localeId;

    /**
     * @var string|null The locale ID that the old locale's exclusive content should be transfered to.
     */
    public $transferContentTo;
}
