<?php
namespace Blocks;

/**
 * Email functions
 */
class EmailMessagesVariable
{
	/**
	 * Returns all of the system email messages.
	 *
	 * @return array
	 */
	public function getAllMessages()
	{
		return blx()->emailMessages->getAllMessages();
	}

	/**
	 * Returns a system email message by its key.
	 *
	 * @param string $key
	 * @param string|null $language
	 * @return EmailMessageModel|null
	 */
	public function getMessage($key, $language = null)
	{
		return blx()->emailMessages->getMessage($key, $language);
	}
}
