<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use yii\web\HttpException;

/**
 * UnavailableHttpException represents a "Service Unavailable" HTTP exception with status code 503.
 */
class ServiceUnavailableHttpException extends HttpException
{
	/**
	 * Constructor.
	 *
	 * @param string $message The error message.
	 * @param integer $code The error code.
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	public function __construct($message = null, $code = 0, \Exception $previous = null)
	{
		parent::__construct(503, $message, $code, $previous);
	}
}
