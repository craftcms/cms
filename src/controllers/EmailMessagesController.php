<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use craft\app\Craft;
use craft\app\models\RebrandEmail   as RebrandEmailModel;
use craft\app\errors\HttpException;

craft()->requireEdition(Craft::Client);

/**
 * The EmailMessagesController class is a controller that handles various email message tasks such as saving email
 * messages.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EmailMessagesController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseController::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All email message actions require an admin
		$this->requireAdmin();
	}

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
