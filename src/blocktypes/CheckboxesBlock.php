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
}
