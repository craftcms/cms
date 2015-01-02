<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;
use craft\app\models\PluginUpdate  as PluginUpdateModel;

/**
 * Stores all of the available update info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Update extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::setAttribute()
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes['app']      = array(AttributeType::Mixed, 'model' => '\craft\app\models\AppUpdate');
		$attributes['plugins']  = AttributeType::Mixed;
		$attributes['errors']   = AttributeType::Mixed;

		return $attributes;
	}
}
