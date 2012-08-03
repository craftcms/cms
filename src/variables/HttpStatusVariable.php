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
		throw new HttpException(404, Blocks::t(TranslationCategory::App, $message));
	}

	/**
	 * @param null $message
	 * @throws HttpException
	 */
	public function status403($message = null)
	{
		throw new HttpException(403, Blocks::t(TranslationCategory::App, $message));
	}

	/**
	 * @param null $message
	 * @throws HttpException
	 */
	public function status500($message = null)
	{
		throw new HttpException(500, Blocks::t(TranslationCategory::App, $message));
	}
}
