<?php
namespace Blocks;

/**
 *
 */
class MultiSelectBlock extends BaseOptionsBlock
{
	protected $settingsTemplate = '_components/blocks/MultiSelect/settings';
	protected $fieldTemplate = '_components/blocks/MultiSelect/field';

	/**
	 * @return string|void
	 */
	public function getName()
	{
		return Blocks::t('Multi-select');
	}

	/**
	 * Combines the multi-select selections into a flat string
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
