<?php
namespace Blocks;

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
		$et = new Et(ElliotEndPoints::Ping);
		$response = $et->phoneHome();

		return $response;
	}

	/**
	 * @param $updateInfo
	 * @return EtModel|bool
	 */
	public function check($updateInfo)
	{
		$et = new Et(ElliotEndPoints::Check);
		$et->getModel()->data = $updateInfo;
		$etModel = $et->phoneHome();

		$etModel = $this->decodeEtUpdateValues($etModel);

		return $etModel;
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
		$et->setDestinationFileName($downloadPath);
		$et->getModel()->data = $params;

		if ($et->phoneHome())
		{
			return true;
		}

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
		$et->getModel()->data = $params;
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
		blx()->fileCache->set('licenseKeyStatus', $status, blx()->config->getCacheDuration());
	}

	/**
	 *
	 */
	public function decodeEtValues($values)
	{
		return EtModel::populateModel(JsonHelper::decode($values));
	}

	/**
	 * @param EtModel $etModel
	 * @return EtModel
	 */
	public function decodeEtUpdateValues(EtModel $etModel)
	{
		$updateModel = UpdateModel::populateModel($etModel->data);
		$blocksUpdateModel = BlocksUpdateModel::populateModel($etModel->data['blocks']);

		$pluginUpdateModels = array();

		foreach ($etModel->data['plugins'] as $key => $pluginAttributes)
		{
			$pluginUpdateModels[$key] = PluginUpdateModel::populateModel($pluginAttributes);
		}

		$updateModel->blocks = $blocksUpdateModel;
		$updateModel->plugins = $pluginUpdateModels;
		$etModel->data = $updateModel;

		return $etModel;
	}
}
