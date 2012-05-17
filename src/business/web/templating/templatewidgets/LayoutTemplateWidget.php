<?php
namespace Blocks;

/**
 *
 */
class LayoutTemplateWidget extends \COutputProcessor
{
	public $template;
	public $variables = array();
	public $regions = array();

	/**
	 * @param $output
	 */
	public function processOutput($output)
	{
		if ($this->template)
		{
			$owner = $this->getOwner();

			$this->variables['subtemplate'] = new StringAdapter($output);

			foreach ($this->regions as $region)
			{
				$this->variables[$region->name] = $region->content;
			}

			$output = $owner->loadTemplate($this->template, $this->variables, true);
		}

		parent::processOutput($output);
	}
}
