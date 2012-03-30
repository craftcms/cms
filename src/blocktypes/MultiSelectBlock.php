<?php
namespace Blocks;

/**
 *
 */
class MultiSelectBlock extends BaseOptionsBlock
{
	public $blocktypeName = 'Multi-select';

	protected $settingsTemplate = '_blocktypes/MultiSelect/settings';
	protected $fieldTemplate = '_blocktypes/MultiSelect/field';

	/**
	 * Combines the multi-select selections into a flat string
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
