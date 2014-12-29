<?php
namespace craft\app\base;

/**
 * Field layout trait.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.base
 * @since     1.2
 */
class FieldLayoutTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_fieldLayout;

	/**
	 * @var
	 */
	private $_elementType;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param $elementType
	 *
	 * @return FieldLayoutTrait
	 */
	public function __construct($elementType)
	{
		$this->_elementType = $elementType;
	}

	/**
	 * Returns the owner's field layout.
	 *
	 * @return FieldLayoutModel
	 */
	public function getFieldLayout()
	{
		if (!isset($this->_fieldLayout))
		{
			if (!empty($this->getOwner()->fieldLayoutId))
			{
				$this->_fieldLayout = craft()->fields->getLayoutById($this->getOwner()->fieldLayoutId);
			}

			if (empty($this->_fieldLayout))
			{
				$this->_fieldLayout = new FieldLayoutModel();
				$this->_fieldLayout->type = $this->_elementType;
			}
		}

		return $this->_fieldLayout;
	}

	/**
	 * Sets the owner's field layout.
	 *
	 * @param FieldLayoutModel $fieldLayout
	 *
	 * @return null
	 */
	public function setFieldLayout(FieldLayoutModel $fieldLayout)
	{
		$this->_fieldLayout = $fieldLayout;
	}
}
