<?php
namespace Blocks;

/**
 *
 */
class EtService extends \CApplicationComponent
{
	/**
	 * @return bool|EtPackage
	 */
	public function ping()
	{
		$et = new Et(ElliotEndPoints::Ping);
		$response = $et->phoneHome();
		return $response;
	}

	/**
	 * @param $updateInfo
	 * @return EtPackage|bool
	 */
	public function check($updateInfo)
	{
		$et = new Et(ElliotEndPoints::Check);
		$et->package->data = $updateInfo;
		$response = $et->phoneHome();
		return $response;
	}

	/**
	 * @param $version
	 * @param $build
	 * @param $downloadPath
	 * @return bool
	 */
	public function downloadPackage($version, $build, $downloadPath)
	{
		$params = array(
			'versionNumber' => $version,
			'buildNumber' => $build,
			'type' => AppReleaseFileType::Patch
		);

		$et = new Et(ElliotEndPoints::DownloadPackage, 60);
		$et->destinationFileName = $downloadPath;
		$et->package->data = $params;
		if ($et->phoneHome())
			return true;

		return false;
	}

	/**
	 * @param $version
	 * @param $build
	 * @return null
	 */
	public function getReleaseMD5($version, $build)
	{
		$params = array(
			'versionNumber' => $version,
			'buildNumber' => $build,
			'type' => AppReleaseFileType::Patch
		);

		$et = new Et(ElliotEndPoints::GetAppReleaseFileMD5);
		$et->package->data = $params;
		$package = $et->phoneHome();

		$sourceMD5 = $package->data;
		return $sourceMD5;
	}

	/**
	 * Returns the license key status.
	 */
	public function getLicenseKeyStatus()
	{
		$status = blx()->fileCache->get('licenseKeyStatus');
		return $status;
	}

	/**
	 * Sets the license key status.
	 */
	public function setLicenseKeyStatus($status)
	{
		blx()->fileCache->set('licenseKeyStatus', $status, blx()->config->cacheTimeSeconds);
	}
}
