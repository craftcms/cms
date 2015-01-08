<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;

/**
 * Class GetHelp model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GetHelp extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		// maxSize is 3MB
		return array_merge(parent::rules(), [
			['attachment', 'file', 'maxSize' => 3145728, 'allowEmpty' => true],
		]);
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
		return [
			'fromEmail'        => [AttributeType::Email, 'required' => true, 'label' => 'Your Email'],
			'message'          => [AttributeType::String, 'required' => true],
			'attachLogs'       => AttributeType::Bool,
			'attachDbBackup'   => AttributeType::Bool,
			'attachTemplates'  => AttributeType::Bool,
			'attachment'       => AttributeType::Mixed,
		];
	}
}
