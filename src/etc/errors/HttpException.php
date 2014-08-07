<?php
namespace Craft;

/**
 * Class HttpException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.errors
 * @since     1.0
 */
class HttpException extends \CHttpException
{
	// Public Methods
	// =========================================================================

	/**
	 * @param      $status
	 * @param null $message
	 * @param int  $code
	 *
	 * @return HttpException
	 */
	public function __construct($status = '', $message = null, $code = 0)
	{
		$status = $status ? $status : '';
		Craft::log(($status ? $status.' - ' : '').$message, LogLevel::Warning);
		parent::__construct($status, $message, $code);
	}
}
