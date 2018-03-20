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
 * @since 3.0
 */
class SiteEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\models\Site|null The site model associated with the event.
     */
    public $site;

    /**
     * @var bool Whether the site is brand new
     */
    public $isNew = false;
}
