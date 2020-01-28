<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Backup event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class BackupEvent extends Event
{
    /**
     * @var string|null The file path to the backup.
     */
    public $file;

    /**
     * @var string[] The table names whose data should be excluded from the backup.
     * @since 3.4.0
     */
    public $ignoreTables;
}
