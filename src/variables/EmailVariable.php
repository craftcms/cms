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
}
