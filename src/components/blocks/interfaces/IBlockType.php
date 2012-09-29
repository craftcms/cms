<?php
namespace Blocks;

/**
 * Block type interface
 */
interface IBlockType extends IComponent
{
	public function defineContentAttribute();
	public function getInputHtml($package, $handle);
}