<?php
namespace Blocks;

/**
 * Handles email related tasks.
 */
class EmailController extends BaseController
{
	/**
	 * Saves an email message
	 */
	public function actionSaveMessage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$messageId = blx()->request->getRequiredPost('messageId');
		$language = blx()->request->getPost('language');
		$subject = blx()->request->getRequiredPost('subject');
		$body = blx()->request->getRequiredPost('body');

		$content = blx()->email->saveMessageContent($messageId, $subject, $body, null, $language);

		if ($content->hasErrors())
			$this->returnErrorJson(Blocks::t('There was a problem saving your message.'));
		else
			$this->returnJson(array('success' => true));
	}
}
