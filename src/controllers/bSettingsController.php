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
			$model->smtpAuth                    = Blocks::app()->request->getPost('smtpAuth') == 'on' ? 1 : null;
			$model->userName                    = Blocks::app()->request->getPost('userName');
			$model->password                    = Blocks::app()->request->getPost('password');
			$model->smtpKeepAlive               = Blocks::app()->request->getPost('smtpKeepAlive') == 'on' ? 1 : null;
			$model->smtpSecureTransport         = Blocks::app()->request->getPost('smtpSecureTransport') == 'on' ? 1 : null;
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
							$settings['smtpAuth'] = 1;
							$settings['userName'] = $model->userName;
							$settings['password'] = $model->password;
						}

						if ($model->smtpSecureTransport)
						{
							$settings['smtpSecureTransport'] = 1;
							$settings['smtpSecureTransportType'] = $model->smtpSecureTransportType;
						}

						$settings['port'] = $model->port;
						$settings['hostName'] = $model->hostName;

						if ($model->smtpKeepAlive)
						{
							$settings['smtpKeepAlive'] = 1;
						}

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
						$settings['smtpSecureTransport'] = 1;
						$settings['smtpSecureTransportType'] = $model->smtpSecureTransportType;
						$settings['userName'] = $model->userName;
						$settings['password'] = $model->password;
						$settings['port'] = $model->smtpSecureTransportType == 'Tls' ? '587' : '465';
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

