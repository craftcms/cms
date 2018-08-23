<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Reorder Sites event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ReorderSitesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string[]|null The site UIDs in their new order
     */
    public $siteUids;
}
