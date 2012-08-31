<?php
namespace Blocks;

/**
 *
 */
class NumberBlock extends BaseBlock
{
	protected $settingsTemplate = '_blocktypes/Number/settings';
	protected $fieldTemplate = '_blocktypes/Number/field';

	public function getType()
	{
		return Blocks::t('Number');
	}

	protected function getDefaultSettings()
	{
		return array(
			'type' => 'int',
			'min' => null,
			'max' => null
		);
	}

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
