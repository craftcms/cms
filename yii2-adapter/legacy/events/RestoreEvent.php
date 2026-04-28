<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * Restore event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Database\Events\BeforeRestoreBackup} or {@see \CraftCms\Cms\Database\Events\AfterRestoreBackup} instead.
 */
class RestoreEvent extends Event
{
    /**
     * @var string The file path to the backup to restore.
     */
    public string $file;
}
