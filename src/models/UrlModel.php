<?php
namespace Craft;

/**
 * URL model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.3
 */
class urlModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var FieldModel|null
	 */
	private $_matrixField;

	/**
	 * @var
	 */
	private $_blockTypes;

	// Public Methods
	// =========================================================================

	/**
	 * Returns this model's validation rules.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		// Use Yii's stricter URL validator here
		$rules[] = array('url', 'url');

		return $rules;
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
			'url' => array(AttributeType::String, 'required' => true, 'label' => 'URL')
		);
	}
}
