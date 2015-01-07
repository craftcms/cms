<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;

/**
 * Class Structure model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Structure extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether elements in this structure can be sorted by the current user.
	 *
	 * @return bool
	 */
	public function isSortable()
	{
		return Craft::$app->getSession()->checkAuthorization('editStructure:'.$this->id);
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
		return array(
			'id'        => AttributeType::Number,
			'maxLevels' => AttributeType::Number,
		);
	}
}
