<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\models\RebrandEmail as RebrandEmailModel;
use craft\app\records\EmailMessage as EmailMessageRecord;
use yii\base\Component;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class EmailMessages service.
 *
 * An instance of the EmailMessages service is globally accessible in Craft via [[Application::emailMessages `Craft::$app->getEmailMessages()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EmailMessages extends Component
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
			$localeId = Craft::$app->language;
		}

		$records = EmailMessageRecord::findAll([
			'locale' => $localeId
		]);

		// Index the records by their keys
		$recordsByKey = [];
		foreach ($records as $record)
		{
			$recordsByKey[$record->key] = $record;
		}

		// Now assemble the whole list of messages
		$messages = [];

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
			$localeId = Craft::$app->language;
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
			$craftSourceLocale = Craft::$app->sourceLanguage;

			$this->_messageKeysAndSourceLocales = [
				'account_activation' => $craftSourceLocale,
				'verify_new_email'   => $craftSourceLocale,
				'forgot_password'    => $craftSourceLocale,
				'test_email'         => $craftSourceLocale,
			];

			// Give plugins a chance to add additional messages
			foreach (Craft::$app->getPlugins()->call('registerEmailMessages') as $pluginHandle => $pluginKeys)
			{
				$pluginSourceLocale = Craft::$app->getPlugins()->getPlugin($pluginHandle)->sourceLanguage;

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

		$t = Craft::t('app', $combinedKey, null, $localeId);

		// If a translation couldn't be found, default to the message's source locale
		if ($t == $combinedKey)
		{
			$sourceLocale = $this->_getMessageSourceLocaleByKey($key);

			if ($sourceLocale)
			{
				$t = Craft::t('app', $combinedKey, null, $sourceLocale);
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
			$localeId = Craft::$app->language;
		}

		$record = EmailMessageRecord::findOne([
			'key'    => $key,
			'locale' => $localeId,
		]);

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
