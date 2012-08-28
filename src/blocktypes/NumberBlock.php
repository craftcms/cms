<?php
namespace Blocks;

/**
 *
 */
class NumberBlock extends BaseBlock
{
	public $blocktypeName = 'Number';

	protected $defaultSettings = array(
		'type' => 'int',
		'min' => null,
		'max' => null
	);

	protected $settingsTemplate = '_blocktypes/Number/settings';
	protected $fieldTemplate = '_blocktypes/Number/field';

	/**
	 * Get the
	 * @return bool|string Whether the settings passed validation
	 */
	public function getColumnType()
	{
		switch ($this->settings['type'])
		{
			case 'int':
				return PropertyType::Int;
			case 'float':
				return PropertyType::Float;
			case 'dec':
				return PropertyType::Decimal;
		}

		return null;
	}
}
