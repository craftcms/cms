<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use yii\web\HttpException;

/**
 * UnavailableHttpException represents a "Service Unavailable" HTTP exception with status code 503.
 */
class ServiceUnavailableHttpException extends HttpException
{
    /**
     * Constructor.
     *
     * @param string|null $message The error message.
     * @param int $code The error code.
     * @param \Exception|null $previous The previous exception used for the exception chaining.
     */
    public function __construct(string $message = null, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(503, $message, $code, $previous);
    }
}
