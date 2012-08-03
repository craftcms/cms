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
		throw new HttpException(404, Blocks::t(TranslationCategory::App, '{404message}', array('{404message}' => $message)));
	}

	/**
	 * @param null $message
	 * @throws HttpException
	 */
	public function status403($message = null)
	{
		throw new HttpException(403, Blocks::t(TranslationCategory::App, '{403message}', array('{403message}' => $message)));
	}

	/**
	 * @param null $message
	 * @throws HttpException
	 */
	public function status500($message = null)
	{
		throw new HttpException(500, Blocks::t(TranslationCategory::App, '{500message}', array('{500message}' => $message)));
	}
}
