<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Class EmailMessagesService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class EmailMessagesService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_messageKeysAndSourceLocales;

	// Public Methods
	// =========================================================================

	/**
	 * Returns all of the system email messages.
	 *
	 * @param string|null $localeId
	 *
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

		foreach ($this->_getAllMessageKeys() as $key)
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
				$message->subject  = $this->_translateMessageString($key, 'subject', $localeId);
				$message->body     = $this->_translateMessageString($key, 'body', $localeId);
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
	 *
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
	 *
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

	// Private Methods
	// =========================================================================

	/**
	 * Returns all email message keys.
	 *
	 * @return array
	 */
	private function _getAllMessageKeys()
	{
		$this->_setAllMessageKeysAndLocales();

		return array_keys($this->_messageKeysAndSourceLocales);
	}

	/**
	 * Returns the source locale for a message by its key.
	 *
	 * @param string $key
	 *
	 * @return string|null
	 */
	private function _getMessageSourceLocaleByKey($key)
	{
		$this->_setAllMessageKeysAndLocales();

		if (isset($this->_messageKeysAndSourceLocales[$key]))
		{
			return $this->_messageKeysAndSourceLocales[$key];
		}
	}

	/**
	 * Sets all of the email message keys and source locales.
	 *
	 * @return null
	 */
	private function _setAllMessageKeysAndLocales()
	{
		if (!isset($this->_messageKeysAndSourceLocales))
		{
			$craftSourceLocale = craft()->sourceLanguage;

			$this->_messageKeysAndSourceLocales = array(
				'account_activation' => $craftSourceLocale,
				'verify_new_email'   => $craftSourceLocale,
				'forgot_password'    => $craftSourceLocale,
				'test_email'         => $craftSourceLocale,
			);

			// Give plugins a chance to add additional messages
			foreach (craft()->plugins->call('registerEmailMessages') as $pluginHandle => $pluginKeys)
			{
				$pluginSourceLocale = craft()->plugins->getPlugin($pluginHandle)->getSourceLanguage();

				foreach ($pluginKeys as $key)
				{
					$this->_messageKeysAndSourceLocales[$key] = $pluginSourceLocale;
				}
			}
		}
	}

	/**
	 * Translates an email message string.
	 *
	 * @param string $key
	 * @param string $part
	 * @param string $localeId
	 *
	 * @return null|string
	 */
	private function _translateMessageString($key, $part, $localeId)
	{
		$combinedKey = $key.'_'.$part;

		$t = Craft::t($combinedKey, null, null, $localeId);

		// If a translation couldn't be found, default to the message's source locale
		if ($t == $combinedKey)
		{
			$sourceLocale = $this->_getMessageSourceLocaleByKey($key);

			if ($sourceLocale)
			{
				$t = Craft::t($combinedKey, null, null, $sourceLocale);
			}
		}

		return $t;
	}

	/**
	 * Gets a message record by its key.
	 *
	 * @param string      $key
	 * @param string|null $localeId
	 *
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
			$record->subject  = $this->_translateMessageString($key, 'subject', $localeId);
			$record->body     = $this->_translateMessageString($key, 'body', $localeId);
		}

		return $record;
	}
}
