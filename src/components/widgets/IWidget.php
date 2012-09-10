<?php
namespace Blocks;

/**
 * Block interface
 */
interface IWidget extends IComponent
{
	public function getTitle();
	public function getWidgetHtml();
}
