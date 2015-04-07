<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\models\PluginUpdate as PluginUpdateModel;

/**
 * Stores the available plugin update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PluginUpdate extends Model
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
				if (!$value instanceof PluginUpdateModel)
				{
					$config['releases'][$key] = PluginUpdateModel::create($value);
				}
			}
		}

		parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * @var string Class
	 */
	public $class;

	/**
	 * @var string Local version
	 */
	public $localVersion;

	/**
	 * @var string Latest version
	 */
	public $latestVersion;

	/**
	 * @var \DateTime Latest date
	 */
	public $latestDate;

	/**
	 * @var boolean Status
	 */
	public $status = false;

	/**
	 * @var string Display name
	 */
	public $displayName;

	/**
	 * @var boolean Critical update available
	 */
	public $criticalUpdateAvailable = false;

	/**
	 * @var array Releases
	 */
	public $releases;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['latestDate'], 'craft\\app\\validators\\DateTime'],
			[['class', 'localVersion', 'latestVersion', 'latestDate', 'status', 'displayName', 'criticalUpdateAvailable', 'releases'], 'safe', 'on' => 'search'],
		];
	}
}
