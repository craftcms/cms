<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\logging;
use craft\app\Craft;

/**
 * LogFilter pre-processes the logged messages before they are handled by a log route.
 *
 * LogFilter is meant to be used by a log route to pre-process the logged messages before they are handled by the route.
 * The default implementation of LogFilter prepends additional context information to the logged messages. In particular,
 * by setting [[logVars]], predefined PHP variables such as $_SERVER, $_POST, etc. can be saved as a log message,
 * which may help identify/debug issues encountered.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class LogFilter extends \CLogFilter
{
	// Public Methods
	// =========================================================================

	/**
	 * @return LogFilter
	 */
	public function __construct()
	{
		$this->dumper = Craft::$app->config->get('logDumpMethod');
	}
}
