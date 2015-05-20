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
 * Stores all of the available update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Update extends Model
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		if (isset($config['plugins']))
		{
			foreach ($config['plugins'] as $key => $value)
			{
				if (!$value instanceof PluginUpdateModel)
				{
					$config['plugins'][$key] = PluginUpdateModel::create($value);
				}
			}
		}

		parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * @var AppUpdate App
	 */
	public $app;

	/**
	 * @var PluginUpdate[] Plugins
	 */
	public $plugins = [];

	/**
	 * @var array Errors
	 */
	public $errors;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['app', 'plugins', 'errors'], 'safe', 'on' => 'search'],
		];
	}
}
