<?php
namespace Blocks;

/**
 *
 */
class EtService extends BaseService
{
	/**
	 * @return bool|EtPackage
	 */
	public function ping()
	{
		$et = new Et(EtEndPoints::Ping());
		$response = $et->phoneHome();
		return $response;
	}
}
