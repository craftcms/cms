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
 * Stores all of the available update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Update extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var array App
	 */
	public $app;

	/**
	 * @var array Plugins
	 */
	public $plugins;

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
		if ($name == 'plugins')
		{
			$value = PluginUpdateModel::populateModels($value);
		}

		parent::setAttribute($name, $value);
	}
}
