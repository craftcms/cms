<?php
namespace Blocks;

/**
 * Email functions
 */
class EmailVariable
{
	/**
	 * Return all of the system email messages
	 *
	 * @return array
	 */
	public function messages()
	{
		$return = array();

		$messages = blx()->email->getAllMessages();
		foreach ($messages as $message)
		{
			$content = blx()->email->getMessageContent($message->id);
			$return[] = array(
				'heading'     => Blocks::t($message->key.'_heading'),
				'id'          => $message->id,
				'subject'     => $content->subject,
				'bodyPreview' => preg_replace('/\s+/', ' ', $content->body)
			);
		}

		return $return;
	}

	/**
	 * Returns a message by its ID.
	 *
	 * @param $messageId
	 */
	public function getMessage($messageId)
	{
		$record = blx()->email->getMessageById($messageId);
		if ($record)
			return new ModelVariable($record);
	}

	/**
	 * Returns a message's content by the message ID.
	 *
	 * @param      $messageId
	 * @param null $language
	 * @return
	 */
	public function getMessageContent($messageId, $language = null)
	{
		$record = blx()->email->getMessageContent($messageId, $language);
		if ($record)
			return new modelVariable($record);
	}
}
