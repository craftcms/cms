<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;
use craft\app\enums\AttributeType;
use craft\app\models\PluginUpdate as PluginUpdateModel;

/**
 * Stores the available plugin update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PluginUpdate extends Model
{
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

	/**
	 * @inheritDoc Model::setAttribute()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return bool|null
	 */
	public function setAttribute($name, $value)
	{
		if ($name == 'releases')
		{
			$value = PluginUpdateModel::populateModels($value);
		}

		parent::setAttribute($name, $value);
	}
}
