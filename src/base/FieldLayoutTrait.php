<?php
namespace craft\app\base;

use craft\app\models\FieldLayout as FieldLayoutModel;

/**
 * FieldLayoutTrait.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.base
 * @since     3.0
 */
trait FieldLayoutTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_fieldLayout;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the owner's field layout.
	 *
	 * @return FieldLayoutModel
	 */
	public function getFieldLayout()
	{
		if (!isset($this->_fieldLayout))
		{
			if (!empty($this->fieldLayoutId))
			{
				$this->_fieldLayout = craft()->fields->getLayoutById($this->fieldLayoutId);
			}

			if (empty($this->_fieldLayout))
			{
				$this->_fieldLayout = new FieldLayoutModel();
				$this->_fieldLayout->type = $this->_fieldLayoutElementType;
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
