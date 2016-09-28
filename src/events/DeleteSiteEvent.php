<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Delete Site event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteSiteEvent extends SiteEvent
{
    // Properties
    // =========================================================================

    /**
     * @var integer|null The site ID that should take over the deleted site’s contents
     */
    public $transferContentTo;
}
