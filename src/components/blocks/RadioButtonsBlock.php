<?php
namespace Blocks;

/**
 *
 */
class RadioButtonsBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_components/blocks/RadioButtons/settings';
	protected $fieldTemplate = '_components/blocks/RadioButtons/field';

	/**
	 * @return string|void
	 */
	public function getType()
	{
		return Blocks::t('Radio Buttons');
	}

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function getColumnType()
	{
		return PropertyType::Varchar;
	}
}
