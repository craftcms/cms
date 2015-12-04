<?php
namespace Craft;

/**
 * LogFilter pre-processes the logged messages before they are handled by a log route.
 *
 * LogFilter is meant to be used by a log route to pre-process the logged messages before they are handled by the route.
 * The default implementation of LogFilter prepends additional context information to the logged messages. In particular,
 * by setting {@link logVars}, predefined PHP variables such as $_SERVER, $_POST, etc. can be saved as a log message,
 * which may help identify/debug issues encountered.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.logging
 * @since     2.3
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
		$this->dumper = craft()->config->get('logDumpMethod');
	}
}
