<?php

/**
 *
 */
class RegionTemplateWidget extends COutputProcessor
{
	public $name;
	public $content;

	/**
	 * @param $output
	 */
	public function processOutput($output)
	{
		$this->content = $output;
		parent::processOutput($output);
	}
}
