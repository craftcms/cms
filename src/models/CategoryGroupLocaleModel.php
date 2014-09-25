<?php
namespace Craft;

/**
 * Category group locale model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     2.0
 */
class CategoryGroupLocaleModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	public $urlFormatIsRequired = false;

	/**
	 * @var bool
	 */
	public $nestedUrlFormatIsRequired = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if ($this->urlFormatIsRequired)
		{
			$rules[] = array('urlFormat', 'required');
		}

		if ($this->nestedUrlFormatIsRequired)
		{
			$rules[] = array('nestedUrlFormat', 'required');
		}

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
			'id'              => AttributeType::Number,
			'groupId'         => AttributeType::Number,
			'locale'          => AttributeType::Locale,
			'urlFormat'       => array(AttributeType::UrlFormat, 'label' => 'URL Format'),
			'nestedUrlFormat' => array(AttributeType::UrlFormat, 'label' => 'URL Format'),
		);
	}
}
