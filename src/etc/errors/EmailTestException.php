<?php
namespace Craft;

/**
 * Class EmailTestException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.errors
 * @since     2.6
 */
class EmailTestException extends Exception
{
	/**
	 * Constructor
	 */
	public function __construct($message = '', $code = 0, $previous = null)
	{
		$message = 'Email settings test failure' . ($message ? ': '.$message : '');
		parent::__construct($message, $code, $previous);
	}
}
