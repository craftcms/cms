<?php
namespace Craft;

/**
 * Class InvalidSubpathException
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.errors
 * @since     2.4
 */
class InvalidSubpathException extends Exception
{
	/**
	 * @var string The invalid subpath
	 */
	public $subpath;

	/**
	 * Constructor.
	 *
	 * @param string  $subpath The invalid subpath
	 * @param string  $message The error message
	 * @param integer $code    The error code
	 */
	public function __construct($subpath, $message = null, $code = 0)
	{
		$this->subpath = $subpath;

		if ($message === null)
		{
			$message = "Could not resolve the subpath “{$subpath}”.";
		}

		parent::__construct($message, $code);
	}
}
