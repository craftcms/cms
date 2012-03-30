<?php
namespace Blocks;

/**
 *
 */
class CheckboxesBlock extends BaseOptionsBlock
{
	public $blocktypeName = 'Checkboxes';

	protected $settingsTemplate = '_blocktypes/Checkboxes/settings';
	protected $fieldTemplate = '_blocktypes/Checkboxes/field';

	/**
	 * Combines the checkbox selections into a flat string
	 */
	public function modifyPostData($data)
	{
		return implode("\n", $data);
	}

	/**
	 * Converts the newline-separated data into an array
	 */
	public function setData($data)
	{
		if (!is_array($data))
			$data = preg_split('/[\r\n]/', $data);
		parent::setData($data);
	}
}
