<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\enums\AttributeType;

Craft::$app->requireEdition(Craft::Client);

/**
 * Email message model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RebrandEmail extends Model
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc Model::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'key'      => AttributeType::String,
			'locale'   => AttributeType::Locale,
			'subject'  => AttributeType::String,
			'body'     => AttributeType::String,
			'htmlBody' => AttributeType::String,
		];
	}
}
