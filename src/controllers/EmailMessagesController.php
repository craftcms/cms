<?php
namespace Craft;

craft()->requireEdition(Craft::Client);

/**
 * Handles email message tasks.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class EmailMessagesController extends BaseController
{
	////////////////////
	// PUBLIC METHODS
	////////////////////

	/**
	 * Saves an email message.
	 *
	 * @return null
	 */
	public function actionSaveMessage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$message = new RebrandEmailModel();
		$message->key = craft()->request->getRequiredPost('key');
		$message->subject = craft()->request->getRequiredPost('subject');
		$message->body = craft()->request->getRequiredPost('body');

		if (craft()->isLocalized())
		{
			$message->locale = craft()->request->getPost('locale');
		}
		else
		{
			$message->locale = craft()->language;
		}

		if (craft()->emailMessages->saveMessage($message))
		{
			$this->returnJson(array('success' => true));
		}
		else
		{
			$this->returnErrorJson(Craft::t('There was a problem saving your message.'));
		}
	}
}
