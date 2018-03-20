<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\SiteGroup;
use yii\base\Event;

/**
 * SiteGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SiteGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var SiteGroup|null The site group associated with this event.
     */
    public $group;

    /**
     * @var bool Whether the site group is brand new
     */
    public $isNew = false;
}
