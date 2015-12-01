<?php
namespace Craft;

/**
 * Field layout behavior.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.behaviors
 * @since     1.2
 */
class FieldLayoutBehavior extends BaseBehavior
{
	// Properties
	// =========================================================================

	/**
	 * @var string The element type that the field layout will be associated with
	 */
	public $elementType;

	/**
	 * @var string The name of the attribute on the owner class that is used to store the field layout’s ID
	 */
	public $idAttribute = 'fieldLayoutId';

	/**
	 * @var
	 */
	private $_fieldLayout;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param string|null $elementType The element type that the field layout will be associated with
	 * @param string|null $idAttribute The name of the attribute on the owner class that is used to store the field layout’s ID
	 */
	public function __construct($elementType = null, $idAttribute = null)
	{
		if ($elementType !== null)
		{
			$this->elementType = $elementType;
		}

		if ($idAttribute !== null)
		{
			$this->idAttribute = $idAttribute;
		}
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
			if (!empty($this->getOwner()->{$this->idAttribute}))
			{
				$this->_fieldLayout = craft()->fields->getLayoutById($this->getOwner()->{$this->idAttribute});
			}

			if (empty($this->_fieldLayout))
			{
				$this->_fieldLayout = new FieldLayoutModel();
				$this->_fieldLayout->type = $this->elementType;
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
