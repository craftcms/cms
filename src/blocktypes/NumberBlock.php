<?php
namespace Blocks;

class NumberBlock extends BaseBlock
{
	public $name = 'Number';

	protected $settings = array(
		'type' => 'int',
		'min' => null,
		'max' => null
	);

	protected $settingsTemplate = '_blocktypes/Number/settings';

	/**
	 * Settings validation
	 * @return bool Whether the settings passed validation
	 */
	public function validateSettings()
	{
		if (!in_array($this->settings['type'], array('int', 'float', 'dec')))
			$this->errors['type'] = 'Type is invalid.';

		return empty($this->errors);
	}

	/**
	 * Get the 
	 * @return bool Whether the settings passed validation
	 */
	public function getColumnType()
	{
		switch ($this->settings['type'])
		{
			case 'int':
				return AttributeType::Int;
			case 'float':
				return AttributeType::Float;
			case 'dec':
				return AttributeType::Decimal;
		}

		return null;
	}
}
