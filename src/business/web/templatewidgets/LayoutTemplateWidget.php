<?php

class LayoutTemplateWidget extends COutputProcessor
{
	public $template;
	public $regions = array();

	public function processOutput($output)
	{
		if ($this->template)
		{
			$owner = $this->getOwner();

			$tags['subtemplate'] = new StringTag($output);

			foreach ($this->regions as $region)
			{
				$tags[$region->name] = new StringTag($region->content);
			}

			$output = $owner->loadTemplate($this->template, $tags, true);
		}

		parent::processOutput($output);
	}
}
