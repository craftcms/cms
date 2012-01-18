<?php

/**
 *
 */
class bEmailService extends CApplicationComponent
{
	public function sendEmail()
	{
		// methods to support
		// 1) mail() // tested with localhost
		// 2) sendmail() // can't test on windows, but installed on some/most? unix servers
		// 3) pop before smtp
		// 4) gmail smtp // tested make sure openssl is enabled in php.ini and you use ssl port 465 or tls port 587
		// 5) smtp no auth // tested on localhost
		// 6) smtp auth

		$email = new PHPMailer();
		$body = 'Html Hello.';

		$email->IsSMTP();
		$email->SMTPAuth = true;
		$email->SMTPDebug = 2;
		$email->Host = 'smtp.gmail.com';
		$email->SMTPSecure = 'ssl';
		$email->Port = 465;
		$email->Username = 'takobell@gmail.com';
		$email->Password = 'NAOAMTxd7F7UkVDihWXd0tUd';

		//$email->Host = 'secure.emailsrvr.com';
		//$email->Port = 25;
		//$email->Username = 'brad@pixelandtonic.com';
		//$email->Password = 'WqYJ3IsKbc1erC';

		$email->From = 'brad@pixelandtonic.com';
		$email->FromName = 'Blocks Admin';
		$email->Subject = 'This is a very important subject.';
		$email->AltBody = 'Plain Text Hello.';

		$email->MsgHTML($body);

		$email->AddReplyTo('brad@pixelandtonic.com', 'Blocks Admin');
		$email->AddAddress('takobell@gmail.com', 'Brad Bell');

		$email->IsHTML(true);

		if (!$email->Send())
		{
			$error = $email->ErrorInfo;
		}


	}
}
