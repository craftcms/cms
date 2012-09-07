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
			'min'      => array(AttributeType::Number, 'default' => 0),
			'max'      => AttributeType::Number,
			'decimals' => array(AttributeType::Number, 'default' => 0),
		);
	}

	/**
	 * @return null|string
	 */
	public function defineContentColumn()
	{
		return DbHelper::getNumberColumnConfig($this->settings->min, $this->settings->max, $this->settings->decimals);
	}
}
