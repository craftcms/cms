<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\models\SiteGroup;

/**
 * SiteGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SiteGroupEvent extends Event
{
    /**
     * @var SiteGroup The site group associated with this event.
     */
    public SiteGroup $group;

    /**
     * @var bool Whether the site group is brand new
     */
    public bool $isNew = false;
}
