<?php

/**
 *
 */
class RegionTemplateWidget extends COutputProcessor
{
	public $name;
	public $content;

	/**
	 * @access public
	 *
	 * @param $output
	 */
	public function processOutput($output)
	{
		$this->content = $output;
		parent::processOutput($output);
	}
}
