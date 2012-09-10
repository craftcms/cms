<?php
namespace Blocks;

/**
 * Block interface
 */
interface IBlock extends IComponent
{
	public function defineContentColumn();
	public function getInputHtml();
}