<?php
namespace Craft;

/**
 *
 */
class EtService extends BaseApplicationComponent
{
	/**
	 * @return bool|EtModel
	 */
	public function ping()
	{
		$et = new Et(ElliottEndpoints::Ping);
		$response = $et->phoneHome();

		return $response;
	}

	/**
	 * @param $updateInfo
	 * @return EtModel|bool
	 */
	public function check($updateInfo)
	{
		$et = new Et(ElliottEndpoints::CheckForUpdates);
		$et->getModel()->data = $updateInfo;
		$etModel = $et->phoneHome();

		if ($etModel)
		{
			$etModel->data = UpdateModel::populateModel($etModel->data);
			return $etModel;
		}

		return null;
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
	 * @param $email
	 * @return EtModel|bool
	 */
	public function createLicense($email)
	{
		$et = new Et(ElliottEndpoints::CreateLicense);
		$et->getModel()->data = $email;
		$etModel = $et->phoneHome();

		if ($etModel && !empty($etModel->data))
		{
			return $etModel->data;
		}

		return null;
	}

	/**
	 * Returns the license key status.
	 */
	public function getLicenseKeyStatus()
	{
		$status = craft()->fileCache->get('licenseKeyStatus');
		return $status;
	}

	/**
	 * Sets the license key status.
	 */
	public function setLicenseKeyStatus($status)
	{
		craft()->fileCache->set('licenseKeyStatus', $status, craft()->config->getCacheDuration());
	}

	/**
	 * Sets the package status information as an array in the format array('name' => packageName, 'status' => packageStatus)
	 *
	 * @param $packageInfo
	 */
	public function setPackageStatuses($packageInfo)
	{
		craft()->fileCache->set('packageStatuses', $packageInfo, craft()->config->getCacheDuration());
	}

	/**
	 * Returns the package status information as an array in the format array('name' => packageName, 'status' => packageStatus)
	 *
	 * @return mixed
	 */
	public function getPackageStatuses()
	{
		$status = craft()->fileCache->get('packageStatuses');
		return $status;
	}

	/**
	 *
	 */
	public function decodeEtValues($values)
	{
		return EtModel::populateModel(JsonHelper::decode($values));
	}
}
