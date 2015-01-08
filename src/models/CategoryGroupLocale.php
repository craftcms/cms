<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\enums\AttributeType;

/**
 * CategoryGroupLocale model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroupLocale extends BaseModel
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
			$rules[] = ['urlFormat', 'required'];
		}

		if ($this->nestedUrlFormatIsRequired)
		{
			$rules[] = ['nestedUrlFormat', 'required'];
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
		return [
			'id'              => AttributeType::Number,
			'groupId'         => AttributeType::Number,
			'locale'          => AttributeType::Locale,
			'urlFormat'       => [AttributeType::UrlFormat, 'label' => 'URL Format'],
			'nestedUrlFormat' => [AttributeType::UrlFormat, 'label' => 'URL Format'],
		];
	}
}
