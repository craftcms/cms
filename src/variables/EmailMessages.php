<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\Craft;
use craft\app\models\RebrandEmail as RebrandEmailModel;

craft()->requireEdition(Craft::Client);

/**
 * Email functions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EmailMessages
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all of the system email messages.
	 *
	 * @return array
	 */
	public function getAllMessages()
	{
		return craft()->emailMessages->getAllMessages();
	}

	/**
	 * Returns a system email message by its key.
	 *
	 * @param string      $key
	 * @param string|null $language
	 *
	 * @return RebrandEmailModel|null
	 */
	public function getMessage($key, $language = null)
	{
		return craft()->emailMessages->getMessage($key, $language);
	}
}
