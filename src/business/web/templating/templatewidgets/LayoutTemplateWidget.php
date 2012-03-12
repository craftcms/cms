<?php
namespace Blocks;

/**
 *
 */
class LayoutTemplateWidget extends \COutputProcessor
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

			$this->tags['subtemplate'] = new StringTag($output);

			foreach ($this->regions as $region)
			{
				$this->tags[$region->name] = $region->content;
			}

			$output = $owner->loadTemplate($this->template, $this->tags, true);
		}

		parent::processOutput($output);
	}
}
