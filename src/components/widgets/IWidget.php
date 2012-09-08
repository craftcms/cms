<?php
namespace Blocks;

/**
 * Block interface
 */
interface IWidget
{
	public function getName();
	public function getWidgetHtml();
	public function getSettingsHtml();
}
