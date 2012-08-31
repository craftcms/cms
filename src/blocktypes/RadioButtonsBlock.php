<?php
namespace Blocks;

/**
 *
 */
class RadioButtonsBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_blocktypes/RadioButtons/settings';
	protected $fieldTemplate = '_blocktypes/RadioButtons/field';
	protected $columnType = PropertyType::Varchar;

	public function getType()
	{
		return Blocks::t('Radio Buttons');
	}
}
