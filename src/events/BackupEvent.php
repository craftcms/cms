<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * Backup event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class BackupEvent extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The file path to the backup.
     */
    public $file;
}
