<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementSupportedSitesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author PixelDeluxe | Tim van Dijkhuizen <tim@pixeldeluxe.nl>
 * @since ctrl_find_replace_this_for_since_supported_sites
 */
class RegisterElementSupportedSitesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array List of registered sites for the element type.
     */
    public $sites = [];
}