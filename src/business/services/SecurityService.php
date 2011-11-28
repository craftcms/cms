<?php

class SecurityService extends CApplicationComponent implements ISecurityService
{
	public function validatePTUserCredentialsAndKey($userName, $password, $licenseKeys, $edition)
	{
		try
		{
			$client = new HttpClient(APIWebServiceEndPoints::ValidateKeysByCredentials, array(
					'timeout'       =>  1,
					'maxredirects'  =>  0
					));

			$client->setParameterPost(array(
				'userName' => $userName,
				'password' => $password,
				'licenseKeys' => $licenseKeys,
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
				Blocks::log('Error in calling '.APIWebServiceEndPoints::ValidateKeysByCredentials.' Response: '.$response->getBody(), 'warning');
				return WebServiceReturnStatus::CODE_404;
			}
		}
		catch(Exception $e)
		{
			Blocks::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), 'error');
		}
	}
}
