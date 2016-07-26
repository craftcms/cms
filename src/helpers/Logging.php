<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

/**
 * Class Logging
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Logging
{
    // Public Methods
    // =========================================================================

    /**
     * Will attempt to remove passwords from the log file.
     *
     * @param $log
     *
     * @return string
     */
    public static function redact($log)
    {
        // Will match 'password => 'secretPassword', which gets logged in the POST params during debug mode.
        $log = preg_replace("/'password' => (')(.*)('),/uim", "'password' => REDACTED,", $log);

        // Will match 'newPassword => 'secretPassword', which gets logged in the POST params during debug mode.
        $log = preg_replace("/'newPassword' => (')(.*)('),/uim", "'newPassword' => REDACTED,", $log);

        // Will match 'smtpPassword => 'secretPassword', which gets logged in the POST params during debug mode.
        $log = preg_replace("/'smtpPassword' => (')(.*)('),/uim", "'newPassword' => REDACTED,", $log);

        return $log;
    }
}
