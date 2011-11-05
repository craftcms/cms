<?php

class SecurityService extends CApplicationComponent implements ISecurityService
{
	public function validatePTUserCredentialsAndKey($userName, $password, $licenseKey, $edition)
	{
		try
		{
			$client = new HttpClient(APIWebServiceEndPoints::ValidateKeyByCredentials, array(
					'timeout'       =>  1,
					'maxredirects'  =>  0
					));

			$client->setParameterPost(array(
				'userName' => $userName,
				'password' => $password,
				'licenseKey' => $licenseKey,
				'edition' => $edition
			));

			$response = $client->request('POST');

			if ($response->isSuccessful())
			{
				$responseBody = $response->getBody();
				return $responseBody;
			}
			else
			{
				Blocks::log('Error in calling '.APIWebServiceEndPoints::ValidateKeyByCredentials.' Response: '.$response->getBody(), 'warning');
				return WebServiceReturnStatus::CODE_404;
			}
		}
		catch(Exception $e)
		{
			Blocks::log('Error in validateUserCredentialsAndKey. Message: '.$e->getMessage(), 'error');
		}
	}
}
