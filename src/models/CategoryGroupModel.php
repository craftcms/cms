<?php
namespace Craft;

/**
 * Category group model.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     2.0
 */
class CategoryGroupModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_locales;

	// Public Methods
	// =========================================================================

	/**
	 * Use the translated category group's name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string)Craft::t($this->name);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(ElementType::Category),
		);
	}

	/**
	 * Returns the category's locale models.
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = craft()->categories->getGroupLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = array();
			}
		}

		return $this->_locales;
	}

	/**
	 * Sets the section's locale models.
	 *
	 * @param array $locales
	 *
	 * @return null
	 */
	public function setLocales($locales)
	{
		$this->_locales = $locales;
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
			'id'            => AttributeType::Number,
			'structureId'   => AttributeType::Number,
			'fieldLayoutId' => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'hasUrls'       => array(AttributeType::Bool, 'default' => true),
			'template'      => AttributeType::String,
			'maxLevels'     => AttributeType::Number,
		);
	}
}
