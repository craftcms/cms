<?php
namespace Blocks;

class NumberBlocktype extends BaseBlocktype
{
	public $blocktypeName = 'Number';

	protected $defaultSettings = array(
		'type' => 'int',
		'min' => null,
		'max' => null
	);

	protected $settingsTemplate = '_blocktypes/Number/settings';

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
