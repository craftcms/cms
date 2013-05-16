<?php
namespace Craft;

/**
 * Tool interface
 */
interface ITool extends IComponentType
{
	/**
	 * @return string
	 */
	public function getOptionsHtml();
}
