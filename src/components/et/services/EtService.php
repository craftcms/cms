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
		$response = $et->phoneHome();

		$this->decodeEtUpdateValues($response);

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
		$etModel = EtModel::populateModel(JsonHelper::decode($values));
		return $this->_convertDataTimeToTimeStamp($etModel);
	}

	/**
	 * @param EtModel $etModel
	 * @return EtModel
	 */
	public function decodeEtUpdateValues(EtModel $etModel)
	{
		$updateModel = UpdateModel::populateModel($etModel->data);
		$updateModel = $this->_convertDataTimeToTimeStamp($updateModel);

		$blocksUpdateModel = BlocksUpdateModel::populateModel($etModel->data['blocks']);
		$blocksUpdateModel = $this->_convertDataTimeToTimeStamp($blocksUpdateModel);

		$pluginsUpdateModel = PluginUpdateModel::populateModels($etModel->data['plugins']);
		$pluginsUpdateModel = $this->_convertDataTimeToTimeStamp($pluginsUpdateModel);

		$updateModel->blocks = $blocksUpdateModel;
		$updateModel->plugins = $pluginsUpdateModel;
		$etModel->data = $updateModel;

		return $etModel;
	}

	/**
	 * @param $model
	 * @return mixed
	 */
	private function _convertDataTimeToTimeStamp($model)
	{
		// Normalize any DateTime objects into timestamps.
		if ($model)
		{
			foreach ($model->defineAttributes() as $name => $config)
			{
				$value = $model->getAttribute($name);
				$config = ModelHelper::normalizeAttributeConfig($config);

				if ($config['type'] == AttributeType::DateTime && (get_class($value) == 'Blocks\DateTime'))
				{
					$value = $value->getTimestamp();
					$model->setAttribute($name, $value);
				}
			}
		}

		return $model;
	}
}
