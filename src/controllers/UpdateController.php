<?php
namespace Craft;

/**
 * The UpdateController class is a controller that handles various update related tasks such as checking for available
 * updates and running manual and auto-updates.
 *
 * Note that all actions in the controller, except for {@link actionPrepare}, {@link actionBackupDatabase},
 * {@link actionUpdateDatabase}, {@link actionCleanUp} and {@link actionRollback} require an authenticated Craft session
 * via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     1.0
 */
class UpdateController extends BaseController
{
	// Properties
	// =========================================================================

	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() craft()->userSession->requireLogin()}
	 * in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = array('actionPrepare', 'actionBackupDatabase', 'actionUpdateDatabase', 'actionCleanUp', 'actionRollback');

	// Public Methods
	// =========================================================================

	// Auto Updates
	// -------------------------------------------------------------------------

	/**
	 * Returns the available updates.
	 *
	 * @return null
	 */
	public function actionGetAvailableUpdates()
	{
		craft()->userSession->requirePermission('performUpdates');

		try
		{
			$updates = craft()->updates->getUpdates(true);
		}
		catch (EtException $e)
		{
			if ($e->getCode() == 10001)
			{
				$this->returnErrorJson($e->getMessage());
			}
		}

		$v3Plugins = array();
		try
		{
			$localInfo = array();

			foreach (craft()->plugins->getPlugins() as $plugin)
			{
				$localInfo['plugins'][] = $plugin->getClassHandle();
			}

			// Look for any remote asset source types
			$remoteSourceTypes = array();
			foreach (craft()->assetSources->getAllSources() as $source) {
				if (in_array($source->type, array('GoogleCloud', 'Rackspace', 'S3'))) {
					$remoteSourceTypes[$source->type] = true;
				}
			}
			if (!empty($remoteSourceTypes))
			{
				$localInfo['assetSourceTypes'] = array_keys($remoteSourceTypes);
			}

			if (!empty($localInfo))
			{
				$client = new \Guzzle\Http\Client();
				$response = $client->post('https://api.craftcms.com/v1/available-plugins')
					->setBody(JsonHelper::encode($localInfo), 'application/json')
					->send();

				if ($response->isSuccessful())
				{
					$v3Plugins = JsonHelper::decode((string)$response->getBody());
					$names = array();

					foreach ($v3Plugins as $handle => &$info)
					{
						if ($plugin = craft()->plugins->getPlugin($handle))
						{
							$info += array(
								'name' => $plugin->getName(),
								'iconUrl' => craft()->plugins->getPluginIconUrl($handle),
								'developerName' => $plugin->getDeveloper(),
								'developerUrl' => $plugin->getDeveloperUrl(),
                            );
						}

						if (array_key_exists('price', $info)) {
							$info['formattedPrice'] = $info['price'] ? craft()->numberFormatter->formatCurrency($info['price'], $info['currency'], true) : 'Free';
						}
						$info['status'] = StringHelper::parseMarkdownLine($info['status']);
						$info['status'] = str_replace('<a ', '<a target="_blank" ', $info['status']);

						$names[] = $info['name'];
					}

					array_multisort($names, $v3Plugins);
				}
			}
		}
		catch (\Exception $e)
		{
		}

		if ($updates)
		{
			$response = $updates->getAttributes();
			$response['allowAutoUpdates'] = craft()->config->allowAutoUpdates();
			$response['v3Plugins'] = array_values($v3Plugins);

			$this->returnJson($response);
		}
		else
		{
			$this->returnErrorJson(Craft::t('Could not fetch available updates at this time.'));
		}
	}

	/**
	 * Called during both a manual and auto-update.
	 *
	 * @return null
	 */
	public function actionPrepare()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');
		$handle = $this->_getFixedHandle($data);

		$manual = false;
		if (!$this->_isManualUpdate($data))
		{
			// If it's not a manual update, make sure they have auto-update permissions.
			craft()->userSession->requirePermission('performUpdates');

			if (!craft()->config->allowAutoUpdates())
			{
				$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
			}
		}
		else
		{
			$manual = true;
		}

		$return = craft()->updates->prepareUpdate($manual, $handle);

		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'finished' => true));
		}

		if ($manual)
		{
			$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Backing-up database…'), 'nextAction' => 'update/backupDatabase', 'data' => $data));
		}
		else
		{
			$data['md5'] = craft()->security->hashData($return['md5']);
			$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Downloading update…'), 'nextAction' => 'update/processDownload', 'data' => $data));
		}

	}

	/**
	 * Called during an auto-update.
	 *
	 * @return null
	 * @throws Exception
	 */
	public function actionProcessDownload()
	{
		// This method should never be called in a manual update.
		craft()->userSession->requirePermission('performUpdates');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (!craft()->config->allowAutoUpdates())
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
		}

		$data = craft()->request->getRequiredPost('data');
		$handle = $this->_getFixedHandle($data);

		$md5 = craft()->security->validateData($data['md5']);

		if (!$md5)
		{
			throw new Exception('Could not validate MD5.');
		}

		$return = craft()->updates->processUpdateDownload($md5, $handle);

		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'finished' => true));
		}

		$data = array(
			'handle' => craft()->security->hashData($handle),
			'uid'    => craft()->security->hashData($return['uid']),
		);

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Backing-up files…'), 'nextAction' => 'update/backupFiles', 'data' => $data));
	}

	/**
	 * Called during an auto-update.
	 *
	 * @return null
	 * @throws Exception
	 */
	public function actionBackupFiles()
	{
		// This method should never be called in a manual update.
		craft()->userSession->requirePermission('performUpdates');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (!craft()->config->allowAutoUpdates())
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
		}

		$data = craft()->request->getRequiredPost('data');
		$handle = $this->_getFixedHandle($data);

		$uid = craft()->security->validateData($data['uid']);

		if (!$uid)
		{
			throw new Exception('Could not validate UID');
		}

		$return = craft()->updates->backupFiles($uid, $handle);

		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'finished' => true));
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Updating files…'), 'nextAction' => 'update/updateFiles', 'data' => $data));
	}

	/**
	 * Called during an auto-update.
	 *
	 * @return null
	 * @throws Exception
	 */
	public function actionUpdateFiles()
	{
		// This method should never be called in a manual update.
		craft()->userSession->requirePermission('performUpdates');

		$this->requirePostRequest();
		$this->requireAjaxRequest();

		if (!craft()->config->allowAutoUpdates())
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => Craft::t('Auto-updating is disabled on this system.'), 'finished' => true));
		}

		$data = craft()->request->getRequiredPost('data');
		$handle = $this->_getFixedHandle($data);
		$uid = craft()->security->validateData($data['uid']);

		if (!$uid)
		{
			throw new Exception('Could not validate UID');
		}

		$return = craft()->updates->updateFiles($uid, $handle);

		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'update/rollback'));
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Backing-up database…'), 'nextAction' => 'update/backupDatabase', 'data' => $data));
	}

	/**
	 * Called during both a manual and auto-update.
	 *
	 * @return null
	 */
	public function actionBackupDatabase()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		$handle = $this->_getFixedHandle($data);

		if (craft()->config->get('backupDbOnUpdate'))
		{
			$plugin = craft()->plugins->getPlugin($handle);

			// If this a plugin, make sure it actually has new migrations before backing up the database.
			if ($handle == 'craft' || ($plugin && craft()->migrations->getNewMigrations($plugin)))
			{
				$return = craft()->updates->backupDatabase();

				if (!$return['success'])
				{
					$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'update/rollback'));
				}

				if (isset($return['dbBackupPath']))
				{
					$data['dbBackupPath'] = craft()->security->hashData($return['dbBackupPath']);
				}
			}
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Updating database…'), 'nextAction' => 'update/updateDatabase', 'data' => $data));
	}

	/**
	 * Called during both a manual and auto-update.
	 *
	 * @return null
	 */
	public function actionUpdateDatabase()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		$handle = $this->_getFixedHandle($data);

		$return = craft()->updates->updateDatabase($handle);

		if (!$return['success'])
		{
			$this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'update/rollback'));
		}

		$this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Cleaning up…'), 'nextAction' => 'update/cleanUp', 'data' => $data));
	}

	/**
	 * Performs maintenance and clean up tasks after an update.
	 *
	 * Called during both a manual and auto-update.
	 *
	 * @return null
	 * @throws Exception
	 */
	public function actionCleanUp()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			$uid = craft()->security->validateData($data['uid']);

			if (!$uid)
			{
				throw new Exception(('Could not validate UID'));
			}
		}

		$handle = $this->_getFixedHandle($data);

		$oldVersion = false;

		// Grab the old version from the manifest data before we nuke it.
		$manifestData = UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid), $handle);

		if ($manifestData && $handle == 'craft')
		{
			$oldVersion = UpdateHelper::getLocalVersionFromManifest($manifestData);
		}

		craft()->updates->updateCleanUp($uid, $handle);

		// New major Craft CMS version?
		if ($handle == 'craft' && $oldVersion && AppHelper::getMajorVersion($oldVersion) < AppHelper::getMajorVersion(craft()->getVersion()))
		{
			$returnUrl = UrlHelper::getUrl('whats-new');
		}
		else
		{
			$returnUrl = craft()->config->get('postCpLoginRedirect');
		}

		$this->returnJson(array('alive' => true, 'finished' => true, 'returnUrl' => $returnUrl));
	}

	/**
	 * Can be called during both a manual and auto-update.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function actionRollback()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$data = craft()->request->getRequiredPost('data');
		$handle = $this->_getFixedHandle($data);

		if ($this->_isManualUpdate($data))
		{
			$uid = false;
		}
		else
		{
			$uid = craft()->security->validateData($data['uid']);

			if (!$uid)
			{
				throw new Exception(('Could not validate UID'));
			}
		}

		if (isset($data['dbBackupPath']))
		{
			$dbBackupPath = craft()->security->validateData($data['dbBackupPath']);

			if (!$dbBackupPath)
			{
				throw new Exception('Could not validate database backup path.');
			}

			$return = craft()->updates->rollbackUpdate($uid, $handle, $dbBackupPath);
		}
		else
		{
			$return = craft()->updates->rollbackUpdate($uid, $handle);
		}

		if (!$return['success'])
		{
			// Let the JS handle the exception response.
			throw new Exception($return['message']);
		}

		$this->returnJson(array('alive' => true, 'finished' => true, 'rollBack' => true));
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $data
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function _isManualUpdate($data)
	{
		if (isset($data['manualUpdate']) && $data['manualUpdate'] == 1)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 * @throws Exception
	 */
	private function _getFixedHandle($data)
	{
		if ($handle = craft()->security->validateData($data['handle']))
		{
			return $handle;
		}

		throw new Exception('Could not validate update handle.');
	}
}
