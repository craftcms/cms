<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RebuildConfigEvent event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.20
 */
class RebuildConfigEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The rebuilt project config
     */
    public $config = [];
}
