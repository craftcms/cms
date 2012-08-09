<?php
namespace Blocks;

/**
 * Handles email related tasks.
 */
class EmailController extends BaseController
{
	/**
	 *
	 */
	public function actionSaveMessage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$messageId = blx()->request->getRequiredPost('messageId');
		$subject = blx()->request->getRequiredPost('subject');
		$body = blx()->request->getRequiredPost('body');

		$content = blx()->email->saveMessageContent($messageId, $subject, $body);

		if ($content->getErrors())
			$this->returnErrorJson('There was a problem saving your message.');
		else
			$this->returnJson(array('success' => true));
	}

	public function actionSendTestEmail()
	{
		$user = blx()->users->getCurrentUser();
		blx()->email->sendEmail($user, 'forgot_password');
	}
}
