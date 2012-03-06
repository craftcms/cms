<?php
namespace Blocks;

/**
 *
 */
class EtService extends BaseComponent
{
	/**
	 * @return bool|EtPackage
	 */
	public function ping()
	{
		$et = new Et(EtEndPoints::Ping);
		$response = $et->phoneHome();
		return $response;
	}
}
