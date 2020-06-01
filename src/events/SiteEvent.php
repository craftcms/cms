<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * Site event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SiteEvent extends CancelableEvent
{
    /**
     * @var \craft\models\Site|null The site model associated with the event.
     */
    public $site;

    /**
     * @var bool Whether the site is brand new
     */
    public $isNew = false;

    /**
     * @var int|null The old primary site ID
     */
    public $oldPrimarySiteId;
}
