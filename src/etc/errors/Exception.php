<?php
namespace Craft;

/**
 * Class Exception
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.errors
 * @since     1.0
 */
class Exception extends \CException
{
	// Public Methods
	// =========================================================================

	/**
	 * @param     $message
	 * @param int $code
	 *
	 * @return Exception
	 */
	public function __construct($message, $code = 0)
	{
		Craft::log($message, LogLevel::Error);
		parent::__construct($message, $code);
	}
}
