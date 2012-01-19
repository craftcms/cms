<?php

/**
 *
 */
class bLayoutTemplateWidget extends COutputProcessor
{
	public $template;
	public $regions = array();

	/**
	 * @param $output
	 */
	public function processOutput($output)
	{
		if ($this->template)
		{
			$owner = $this->owner;

			$tags['subtemplate'] = new bStringTag($output);

			foreach ($this->regions as $region)
			{
				$tags[$region->name] = new bStringTag($region->content);
			}

			$output = $owner->loadTemplate($this->template, $tags, true);
		}

		parent::processOutput($output);
	}
}
