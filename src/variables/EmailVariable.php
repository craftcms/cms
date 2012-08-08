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
				'heading'     => Blocks::t(TranslationCategory::Email, $message->key.'_heading'),
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
	public function getMessageContent($messageId)
	{
		$content = blx()->email->getMessageContent($messageId);
		return $content;
	}
}
