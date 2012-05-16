<?php
namespace Blocks;

/**
 * Returns HTTP Status codes.
 */
class HttpStatusVariable
{
	/**
	 * @param null $message
	 * @throws HttpException
	 */
	public function status404($message = null)
	{
		throw new HttpException(404, $message);
	}

	/**
	 * @param null $message
	 * @throws HttpException
	 */
	public function status403($message = null)
	{
		throw new HttpException(403, $message);
	}

	/**
	 * @param null $message
	 * @throws HttpException
	 */
	public function status500($message = null)
	{
		throw new HttpException(500, $message);
	}
}
