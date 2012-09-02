<?php
namespace Blocks;

/**
 *
 */
class NumberBlock extends BaseBlock
{
	protected $settingsTemplate = '_blocktypes/Number/settings';
	protected $fieldTemplate = '_blocktypes/Number/field';

	/**
	 * @return string|void
	 */
	public function getType()
	{
		return Blocks::t('Number');
	}

	/**
	 * @return array
	 */
	protected function getDefaultSettings()
	{
		return array(
			'type' => 'int',
			'min' => null,
			'max' => null
		);
	}

	/**
	 * @return null|string
	 */
	public function getColumnType()
	{
		switch ($this->settings['type'])
		{
			case 'int':
				return PropertyType::Int;
			case 'dec':
				return PropertyType::Decimal;
		}

		return null;
	}
}
