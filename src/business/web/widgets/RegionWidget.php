<?php

class RegionWidget extends COutputProcessor
{
	public $name;
	public $content;

	public function processOutput($output)
	{
		$this->content = $output;
		parent::processOutput($output);
	}
}
