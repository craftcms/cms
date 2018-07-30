<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Parse config event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ParseConfigEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The config path being parsed
     */
    public $configPath;

    /**
     * @var array The config data found in the config data
     */
    public $configData = [];

    /**
     * @var array The config data found in the snapshot
     */
    public $snapshotData = [];
}
