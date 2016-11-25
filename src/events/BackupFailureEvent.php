<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * Backup failure event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class BackupFailureEvent extends BackupEvent
{
    // Properties
    // =========================================================================

    /**
     * @var string The exit code from the console.
     */
    public $exitCode;

    /**
     * @var string The error message from the console.
     */
    public $errorMessage;
}
