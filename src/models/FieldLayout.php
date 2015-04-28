<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\base\Model;

/**
 * FieldLayout model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayout extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var string Type
	 */
	public $type;


	/**
	 * @var
	 */
	private $_tabs;

	/**
	 * @var
	 */
	private $_fields;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['type'], 'string', 'max' => 150],
			[['id', 'type'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Returns the layout’s tabs.
	 *
	 * @return FieldLayoutTab[] The layout’s tabs.
	 */
	public function getTabs()
	{
		if (!isset($this->_tabs))
		{
			if ($this->id)
			{
				$this->_tabs = Craft::$app->getFields()->getLayoutTabsById($this->id);
			}
			else
			{
				$this->_tabs = [];
			}
		}

		return $this->_tabs;
	}

	/**
	 * Returns the layout’s fields.
	 *
	 * @return FieldInterface[]|Field[] The layout’s fields.
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			if ($this->id)
			{
				$this->_fields = Craft::$app->getFields()->getFieldsByLayoutId($this->id);
			}
			else
			{
				$this->_fields = [];
			}
		}

		return $this->_fields;
	}

	/**
	 * Returns the layout’s fields’ IDs.
	 *
	 * @return array The layout’s fields’ IDs.
	 */
	public function getFieldIds()
	{
		$ids = [];

		foreach ($this->getFields() as $field)
		{
			$ids[] = $field->id;
		}

		return $ids;
	}

	/**
	 * Sets the layout’s tabs.
	 *
	 * @param array|FieldLayoutTab[] $tabs An array of the layout’s tabs, which can either be FieldLayoutTab
	 *                                     objects or arrays defining the tab’s attributes.
	 *
	 * @return null
	 */
	public function setTabs($tabs)
	{
		$this->_tabs = [];

		foreach ($tabs as $tab)
		{
			if (is_array($tab))
			{
				$tab = new FieldLayoutTab($tab);
			}

			$tab->setLayout($this);
			$this->_tabs[] = $tab;
		}
	}

	/**
	 * Sets the layout']”s fields.
	 *
	 * @param FieldInterface[]|Field[] $fields An array of the layout’s fields, which can either be
	 *                                         FieldLayoutFieldModel objects or arrays defining the tab’s
	 *                                         attributes.
	 *
	 * @return null
	 */
	public function setFields($fields)
	{
		$this->_fields = $fields;
	}
}
