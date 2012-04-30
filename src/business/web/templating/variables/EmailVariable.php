<?php
namespace Blocks;

/**
 * Email functions
 */
class EmailVariable
{
	/**
	 * Returns the email settings
	 */
	public function settings()
	{
		return b()->email->getEmailSettings();
	}
}
