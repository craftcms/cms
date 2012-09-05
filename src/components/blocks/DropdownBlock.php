<?php
namespace Blocks;

/**
 *
 */
class DropdownBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_components/blocks/Dropdown/settings';
	protected $fieldTemplate = '_components/blocks/Dropdown/field';

	/**
	 * @return string|void
	 */
	public function getName()
	{
		return Blocks::t('Dropdown');
	}

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function getColumn()
	{
		return PropertyType::Varchar;
	}

}
