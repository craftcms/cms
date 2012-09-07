<?php
namespace Blocks;

/**
 *
 */
class NumberBlock extends BaseBlock
{
	protected $settingsTemplate = '_components/blocks/Number/settings';
	protected $fieldTemplate = '_components/blocks/Number/field';

	/**
	 * @return string|void
	 */
	public function getName()
	{
		return Blocks::t('Number');
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'min'      => array(ColumnType::Decimal, 'default' => 0),
			'max'      => array(ColumnType::Decimal),
			'decimals' => array(ColumnType::Int, 'default' => 0),
		);
	}

	/**
	 * @return null|string
	 */
	public function defineContentColumn()
	{
		return array(AttributeType::Number,
			'min'      => $this->settings['min'],
			'max'      => $this->settings['max'],
			'decimals' => $this->settings['decimals']
		);
	}
}
