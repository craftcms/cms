<?php
namespace Craft;

/**
 * Class GetHelpModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class GetHelpModel extends BaseModel
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
		return array_merge(parent::rules(), array(
			array('attachment', 'file', 'maxSize' => 3145728, 'allowEmpty' => true),
		));
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
			'fromEmail'        => array(AttributeType::Email, 'required' => true, 'label' => 'Your Email'),
			'message'          => array(AttributeType::String, 'required' => true),
			'attachLogs'       => AttributeType::Bool,
			'attachDbBackup'   => AttributeType::Bool,
			'attachTemplates'  => AttributeType::Bool,
			'attachment'       => AttributeType::Mixed,
		);
	}
}
