<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * Reorder Sites event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Events\SitesReordering} or {@see \CraftCms\Cms\Site\Events\SitesReordered}
 */
class ReorderSitesEvent extends Event
{
    /**
     * @var int[] The site IDs in their new order
     */
    public ?array $siteIds = null;
}
