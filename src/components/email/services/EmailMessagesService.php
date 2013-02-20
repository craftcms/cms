<?php
namespace Blocks;

Blocks::requirePackage(BlocksPackage::Rebrand);

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
			$localeId = blx()->language;
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

		$keys = array('verify_email', 'verify_new_email', 'forgot_password');

		// Give plugins a chance to add additional messages
		foreach (blx()->plugins->callHook('registerEmailMessages') as $pluginKeys)
		{
			$keys = array_merge($keys, $pluginKeys);
		}

		foreach ($keys as $key)
		{
			$message = new EmailMessageModel();
			$message->key = $key;
			$message->locale = $localeId;

			// Is there a custom message?
			if (isset($recordsByKey[$key]))
			{
				$message->subject  = $recordsByKey[$key]->subject;
				$message->body     = $recordsByKey[$key]->body;
				$message->htmlBody = $recordsByKey[$key]->htmlBody;
			}
			else
			{
				// Default to whatever's in the translation file
				$message->subject  = Blocks::t($key.'_subject');
				$message->body     = Blocks::t($key.'_body');
				$message->htmlBody = Blocks::t($key.'_html_body');
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
	 * @return EmailMessageModel
	 */
	public function getMessage($key, $localeId = null)
	{
		if (!$localeId)
		{
			$localeId = blx()->language;
		}

		$message = new EmailMessageModel();
		$message->key = $key;
		$message->locale = $localeId;

		$record = $this->_getMessageRecord($key, $localeId);

		$message->subject  = $record->subject;
		$message->body     = $record->body;
		$message->htmlBody = $record->htmlBody;

		return $message;
	}

	/**
	 * Saves the localized content for a system email message.
	 *
	 * @param EmailMessageModel $message
	 * @return bool
	 */
	public function saveMessage(EmailMessageModel $message)
	{
		$record = $this->_getMessageRecord($message->key, $message->locale);

		$record->subject  = $message->subject;
		$record->body     = $message->body;
		$record->htmlBody = $message->htmlBody;

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
			$localeId = blx()->language;
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
			$record->subject  = Blocks::t($key.'_subject');
			$record->body     = Blocks::t($key.'_body');
			$record->htmlBody = Blocks::t($key.'_html_body');
		}

		return $record;
	}
}
