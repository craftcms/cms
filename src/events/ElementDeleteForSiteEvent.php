<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Before Delete For Site Event.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class ElementDeleteForSiteEvent extends ElementEvent
{
    /**
     * @var int Site ID for deletion
     */
    public int $siteId;
}
