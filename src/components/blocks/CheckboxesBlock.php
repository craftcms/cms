<?php
namespace Blocks;

/**
 *
 */
class CheckboxesBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_components/blocks/Checkboxes/settings';
	protected $fieldTemplate = '_components/blocks/Checkboxes/field';

	/**
	 * @return string|void
	 */
	public function getType()
	{
		return Blocks::t('Checkboxes');
	}

	/**
	 * Combines the checkbox selections into a flat string
	 *
	 * @param $data
	 * @return string
	 */
	public function modifyPostData($data)
	{
		return implode("\n", $data);
	}

	/**
	 * Converts the newline-separated data into an array
	 *
	 * @param $data
	 */
	public function setData($data)
	{
		if (!is_array($data))
			$data = preg_split('/[\r\n]/', $data);
		parent::setData($data);
	}
}
