<?php
namespace Blocks;

/**
 *
 */
class RadioButtonsBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_blocktypes/RadioButtons/settings';
	protected $fieldTemplate = '_blocktypes/RadioButtons/field';

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
