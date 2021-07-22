<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\Site;

/**
 * Site event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SiteEvent extends CancelableEvent
{
    /**
     * @var Site The site model associated with the event.
     */
    public Site $site;

    /**
     * @var bool Whether the site is brand new
     */
    public bool $isNew = false;

    /**
     * @var int|null The old primary site ID
     */
    public ?int $oldPrimarySiteId = null;
}
