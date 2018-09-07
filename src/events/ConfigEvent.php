<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Config event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ConfigEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The config path being processed
     */
    public $path;

    /**
     * @var array The old config item value
     */
    public $oldValue = [];

    /**
     * @var array The new config item value
     */
    public $newValue = [];
}
