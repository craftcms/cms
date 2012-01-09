<?php

class SecurityService extends CApplicationComponent implements ISecurityService
{
	public function validatePTUserCredentialsAndKey($userName, $password, $licenseKeys, $edition)
	{
		$params = array(
			'userName' => $userName,
			'password' => $password,
			'licenseKeys' => $licenseKeys,
			'edition' => $edition
		);

		$et = new ET(ETEndPoints::ValidateKeysByCredentials());
		$et->getPackage()->data = $params;
		$et->phoneHome();
	}
}
