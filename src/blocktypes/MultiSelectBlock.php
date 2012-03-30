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
}
