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
 * FieldLayoutTab model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutTab extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Layout ID
	 */
	public $layoutId;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Sort order
	 */
	public $sortOrder;

	/**
	 * @var
	 */
	private $_layout;

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
			[['layoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['name'], 'string', 'max' => 255],
			[['sortOrder'], 'string', 'max' => 4],
			[['id', 'layoutId', 'name', 'sortOrder'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Returns the tab’s layout.
	 *
	 * @return FieldLayout|null The tab’s layout.
	 */
	public function getLayout()
	{
		if (!isset($this->_layout))
		{
			if ($this->layoutId)
			{
				$this->_layout = Craft::$app->getFields()->getLayoutById($this->layoutId);
			}
			else
			{
				$this->_layout = false;
			}
		}

		if ($this->_layout)
		{
			return $this->_layout;
		}
	}

	/**
	 * Sets the tab’s layout.
	 *
	 * @param FieldLayout $layout The tab’s layout.
	 *
	 * @return null
	 */
	public function setLayout(FieldLayout $layout)
	{
		$this->_layout = $layout;
	}

	/**
	 * Returns the tab’s fields.
	 *
	 * @return FieldInterface[]|Field[] The tab’s fields.
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			$this->_fields = [];

			$layout = $this->getLayout();

			if ($layout)
			{
				$fields = $layout->getFields();

				foreach ($fields as $field)
				{
					if ($field->tabId == $this->id)
					{
						$this->_fields[] = $field;
					}
				}
			}
		}

		return $this->_fields;
	}

	/**
	 * Sets the tab’s fields.
	 *
	 * @param FieldInterface[]|Field[] $fields The tab’s fields.
	 *
	 * @return null
	 */
	public function setFields($fields)
	{
		$this->_fields = $fields;
	}
}
