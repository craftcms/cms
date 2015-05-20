<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\models\AppNewRelease as AppNewReleaseModel;

/**
 * Stores the available Craft update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AppUpdate extends Model
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		if (isset($config['releases']))
		{
			foreach ($config['releases'] as $key => $value)
			{
				if (!$value instanceof AppNewReleaseModel)
				{
					$config['releases'][$key] = AppNewReleaseModel::create($value);
				}
			}
		}

		parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * @var string Local build
	 */
	public $localBuild;

	/**
	 * @var string Local version
	 */
	public $localVersion;

	/**
	 * @var string Latest version
	 */
	public $latestVersion;

	/**
	 * @var string Latest build
	 */
	public $latestBuild;

	/**
	 * @var \DateTime Latest date
	 */
	public $latestDate;

	/**
	 * @var string Target version
	 */
	public $targetVersion;

	/**
	 * @var string Target build
	 */
	public $targetBuild;

	/**
	 * @var string Real latest version
	 */
	public $realLatestVersion;

	/**
	 * @var string Real latest build
	 */
	public $realLatestBuild;

	/**
	 * @var \DateTime Real latest date
	 */
	public $realLatestDate;

	/**
	 * @var boolean Critical update available
	 */
	public $criticalUpdateAvailable = false;

	/**
	 * @var boolean Manual update required
	 */
	public $manualUpdateRequired = false;

	/**
	 * @var boolean Breakpoint release
	 */
	public $breakpointRelease = false;

	/**
	 * @var string License updated
	 */
	public $licenseUpdated;

	/**
	 * @var string Version update status
	 */
	public $versionUpdateStatus;

	/**
	 * @var string Manual download endpoint
	 */
	public $manualDownloadEndpoint;

	/**
	 * @var array Releases
	 */
	public $releases = [];

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['latestDate'], 'craft\\app\\validators\\DateTime'],
			[['realLatestDate'], 'craft\\app\\validators\\DateTime'],
			[['localBuild', 'localVersion', 'latestVersion', 'latestBuild', 'latestDate', 'targetVersion', 'targetBuild', 'realLatestVersion', 'realLatestBuild', 'realLatestDate', 'criticalUpdateAvailable', 'manualUpdateRequired', 'breakpointRelease', 'licenseUpdated', 'versionUpdateStatus', 'manualDownloadEndpoint', 'releases'], 'safe', 'on' => 'search'],
		];
	}
}
