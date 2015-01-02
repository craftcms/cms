<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;

craft()->requireEdition(Craft::Client);

/**
 * Email message model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RebrandEmail extends BaseModel
{
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
			'key'      => AttributeType::String,
			'locale'   => AttributeType::Locale,
			'subject'  => AttributeType::String,
			'body'     => AttributeType::String,
			'htmlBody' => AttributeType::String,
		);
	}
}
