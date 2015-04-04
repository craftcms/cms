<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

/**
 * Class LoggingHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class LoggingHelper
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
