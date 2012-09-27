<?php
namespace Blocks;

/**
 * Block interface
 */
interface IBlock extends IComponent
{
	public function defineContentAttribute();
	public function getInputHtml($package, $handle);
}