<?php

class ETService extends CApplicationComponent implements IETService
{
	public function ping()
	{
		$et = new ET(ETEndPoints::Ping());
		$response = $et->phoneHome();
		return $response;
	}
}
