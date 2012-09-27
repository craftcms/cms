<?php
namespace Blocks;

/**
 * Widget interface
 */
interface IWidget extends IComponent
{
	public function getTitle();
	public function getBodyHtml();
}
