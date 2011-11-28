<?php

class LayoutTemplateWidget extends COutputProcessor
{
	public $view;
	public $regions = array();

	public function processOutput($output)
	{
		if ($this->view)
		{
			$owner = $this->getOwner();

			$tags['subtemplate'] = new StringTag($output);

			foreach ($this->regions as $region)
			{
				$tags[$region->name] = new StringTag($region->content);
			}

			$output = $owner->loadTemplate($this->view, $tags, true);
		}

		parent::processOutput($output);
	}
}
