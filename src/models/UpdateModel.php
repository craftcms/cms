<?php
namespace Craft;

/**
 * Stores all of the available update info.
 */
class UpdateModel extends BaseModel
{
	/**
	 * @return array|void
	 */
	public function defineAttributes()
	{
		$attributes['app']      = array(AttributeType::Mixed, 'model' => 'AppUpdateModel');
		$attributes['plugins']  = AttributeType::Mixed;
		$attributes['errors']   = AttributeType::Mixed;

		return $attributes;
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return bool|void
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
