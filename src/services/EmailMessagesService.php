<?php
namespace Craft;

craft()->requirePackage(CraftPackage::Rebrand);

/**
 *
 */
class EmailMessagesService extends BaseApplicationComponent
{
	/**
	 * Returns all of the system email messages.
	 *
	 * @param string|null $localeId
	 * @return array
	 */
	public function getAllMessages($localeId = null)
	{
		// Find any custom messages
		if (!$localeId)
		{
			$localeId = craft()->language;
		}

		$records = EmailMessageRecord::model()->findAllByAttributes(array(
			'locale' => $localeId
		));

		// Index the records by their keys
		$recordsByKey = array();
		foreach ($records as $record)
		{
			$recordsByKey[$record->key] = $record;
		}

		// Now assemble the whole list of messages
		$messages = array();

		$keys = array('account_activation', 'verify_new_email', 'forgot_password');

		// Give plugins a chance to add additional messages
		foreach (craft()->plugins->call('registerEmailMessages') as $pluginKeys)
		{
			$keys = array_merge($keys, $pluginKeys);
		}

		foreach ($keys as $key)
		{
			$message = new RebrandEmailModel();
			$message->key = $key;
			$message->locale = $localeId;

			// Is there a custom message?
			if (isset($recordsByKey[$key]))
			{
				$message->subject  = $recordsByKey[$key]->subject;
				$message->body     = $recordsByKey[$key]->body;
			}
			else
			{
				// Default to whatever's in the translation file
				$message->subject  = Craft::t($key.'_subject', null, null, 'en_us');
				$message->body     = Craft::t($key.'_body', null, null, 'en_us');
			}

			$messages[] = $message;
		}

		return $messages;
	}

	/**
	 * Returns a system email message by its key.
	 *
	 * @param string $key
	 * @param string|null $localeId
	 * @return RebrandEmailModel
	 */
	public function getMessage($key, $localeId = null)
	{
		if (!$localeId)
		{
			$localeId = craft()->language;
		}

		$message = new RebrandEmailModel();
		$message->key = $key;
		$message->locale = $localeId;

		$record = $this->_getMessageRecord($key, $localeId);

		$message->subject  = $record->subject;
		$message->body     = $record->body;

		return $message;
	}

	/**
	 * Saves the localized content for a system email message.
	 *
	 * @param RebrandEmailModel $message
	 * @return bool
	 */
	public function saveMessage(RebrandEmailModel $message)
	{
		$record = $this->_getMessageRecord($message->key, $message->locale);

		$record->subject  = $message->subject;
		$record->body     = $message->body;

		if ($record->save())
		{
			return true;
		}
		else
		{
			$message->addErrors($record->getErrors());
			return false;
		}
	}

	/**
	 * Gets a message record by its key.
	 *
	 * @access private
	 * @param string $key
	 * @param string|null $localeId
	 * @return EmailMessageRecord
	 */
	private function _getMessageRecord($key, $localeId = null)
	{
		if (!$localeId)
		{
			$localeId = craft()->language;
		}

		$record = EmailMessageRecord::model()->findByAttributes(array(
			'key'    => $key,
			'locale' => $localeId,
		));

		if (!$record)
		{
			$record = new EmailMessageRecord();
			$record->key = $key;
			$record->locale   = $localeId;
			$record->subject  = Craft::t($key.'_subject', null, null, 'en_us');
			$record->body     = Craft::t($key.'_body', null, null, 'en_us');
		}

		return $record;
	}
}
