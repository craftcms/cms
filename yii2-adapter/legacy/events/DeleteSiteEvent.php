<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * Delete Site event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Events\DeletingSite} or {@see \CraftCms\Cms\Site\Events\SiteDeleted}.
 */
class DeleteSiteEvent extends SiteEvent
{
    /**
     * @var int|null The site ID that should take over the deleted site’s contents
     */
    public ?int $transferContentTo = null;
}
