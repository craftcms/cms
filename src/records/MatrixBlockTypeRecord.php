<?php
namespace Craft;

/**
 * Stores Matrix block types.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.3
 */
class MatrixBlockTypeRecord extends BaseRecord
{
	// Properties
	// =========================================================================

	/**
	 * Whether the Name and Handle attributes should validated to ensure they’re unique.
	 *
	 * @var bool
	 */
	protected $validateUniques = true;

	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'matrixblocktypes';
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'field'       => array(static::BELONGS_TO, 'FieldRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'fieldId'), 'unique' => true),
			array('columns' => array('handle', 'fieldId'), 'unique' => true),
		);
	}

	/**
	 * Returns this model's validation rules.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if (!$this->validateUniques)
		{
			foreach ($rules as $i => $rule)
			{
				if ($rule[1] == 'Craft\CompositeUniqueValidator')
				{
					unset($rules[$i]);
				}
			}
		}

		return $rules;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'       => array(AttributeType::Name, 'required' => true),
			'handle'     => array(AttributeType::Handle, 'required' => true),
			'sortOrder'  => AttributeType::SortOrder,
		);
	}
}
