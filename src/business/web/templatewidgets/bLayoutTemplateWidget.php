<?php

/**
 *
 */
class bLayoutTemplateWidget extends COutputProcessor
{
	public $template;
	public $tags = array();
	public $regions = array();

	/**
	 * @param $output
	 */
	public function processOutput($output)
	{
		if ($this->template)
		{
			$owner = $this->owner;

			$this->tags['subtemplate'] = new bStringTag($output);

			foreach ($this->regions as $region)
			{
				$this->tags[$region->name] = $region->content;
			}

			$output = $owner->loadTemplate($this->template, $this->tags, true);
		}

		parent::processOutput($output);
	}
}
