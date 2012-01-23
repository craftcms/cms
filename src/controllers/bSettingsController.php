<?php

/**
 * Handles settings from the control panel.
 */
class bSettingsController extends bBaseController
{
	/**
	 *
	 */
	public function actionSaveEmail()
	{
		$model = new bEmailSettingsForm();
		$gMailSmtp = 'smtp.google.com';

		// Check to see if it's a submit.
		if(Blocks::app()->request->isPostRequest)
		{
			$model->emailerType                   = Blocks::app()->request->getPost('emailerType');
			$model->hostName                    = Blocks::app()->request->getPost('hostName');
			$model->port                        = Blocks::app()->request->getPost('port');
			$model->smtpAuth                    = Blocks::app()->request->getPost('smtpAuth');
			$model->userName                    = Blocks::app()->request->getPost('userName');
			$model->password                    = Blocks::app()->request->getPost('password');
			$model->smtpKeepAlive               = Blocks::app()->request->getPost('smtpKeepAlive');
			$model->smtpSecureTransport         = Blocks::app()->request->getPost('smtpSecureTransport');
			$model->smtpSecureTransportType     = Blocks::app()->request->getPost('smtpSecureTransportType');

			// validate user input
			if($model->validate())
			{
				$settings = array('emailerType' => $model->emailerType);
				switch ($model->emailerType)
				{
					case bEmailerType::Smtp:
					{
						if ($model->smtpAuth)
						{
							$settings['smtpAuth'] = true;
							$settings['userName'] = $model->userName;
							$settings['password'] = $model->password;
						}

						if ($model->smtpSecureTransport)
						{
							$settings['smtpSecureTransport'] = true;
							$settings['smtpSecureTransportType'] = $model->smtpSecureTransportType;
						}

						$settings['port'] = $model->port;
						$settings['hostName'] = $model->hostName;
						$settings['smtpKeepAlive'] = $model->smtpKeepAlive;

						break;
					}

					case bEmailerType::Pop:
					{
						$settings['port'] = $model->port;
						$settings['hostName'] = $model->hostName;
						$settings['userName'] = $model->userName;
						$settings['password'] = $model->password;

						break;
					}

					case bEmailerType::GmailSmtp:
					{
						$settings['host'] = $gMailSmtp;
						$settings['smtpSecureTransport'] = true;
						$settings['smtpSecureTransportType'] = $model->smtpSecureTransportType;
						break;
					}
				}

				if (Blocks::app()->email->saveEmailSettings($settings))
				{
					$this->redirect(bUrlHelper::generateUrl('settings/info'));
				}
			}
		}

		// display the login form
		$this->loadTemplate('settings/info', array('emailSettings' => $model));
	}
}

