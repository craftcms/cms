<?php
namespace Craft;

/**
 *
 */
class EtService extends BaseApplicationComponent
{
	/**
	 * @return EtModel|null
	 */
	public function ping()
	{
		$et = new Et(ElliottEndpoints::Ping);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * @param $updateInfo
	 * @return EtModel|null
	 */
	public function check($updateInfo)
	{
		$et = new Et(ElliottEndpoints::CheckForUpdates);
		$et->getModel()->data = $updateInfo;
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			$etResponse->data = UpdateModel::populateModel($etResponse->data);
			return $etResponse;
		}
	}

	/**
	 * @param $downloadPath
	 * @return bool
	 */
	public function downloadUpdate($downloadPath)
	{
		$et = new Et(ElliottEndpoints::DownloadUpdate, 240);

		if (IOHelper::folderExists($downloadPath))
		{
			$downloadPath .= StringHelper::UUID().'.zip';
		}

		$et->setDestinationFileName($downloadPath);

		if (($fileName = $et->phoneHome()) !== null)
		{
			return $fileName;
		}

		return false;
	}

	/**
	 * Fetches info about the available packages from Elliott.
	 *
	 * @return EtModel|null
	 */
	public function fetchPackageInfo()
	{
		$et = new Et(ElliottEndpoints::GetPackageInfo);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * Returns the license key status.
	 */
	public function getLicenseKeyStatus()
	{
		return craft()->fileCache->get('licenseKeyStatus');
	}

	/**
	 * Returns an array of the packages that this license is tied to.
	 *
	 * @return mixed
	 */
	public function getLicensedPackages()
	{
		return craft()->fileCache->get('licensedPackages');
	}

	/**
	 *
	 */
	public function decodeEtValues($values)
	{
		return EtModel::populateModel(JsonHelper::decode($values));
	}
}
