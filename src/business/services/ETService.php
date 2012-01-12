<?php

/**
 *
 */
class ETService extends CApplicationComponent
{
	/**
	 * @access public
	 *
	 * @return bool|ETPackage
	 */
	public function ping()
	{
		$et = new ET(ETEndPoints::Ping());
		$response = $et->phoneHome();
		return $response;
	}
}
