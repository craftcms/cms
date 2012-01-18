<?php

/**
 *
 */
class BEtService extends CApplicationComponent
{
	/**
	 * @return bool|bEtPackage
	 */
	public function ping()
	{
		$et = new bEt(bEtEndPoints::Ping());
		$response = $et->phoneHome();
		return $response;
	}
}
