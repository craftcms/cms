<?php
namespace Blocks;

/**
 * Block interface
 */
interface IBlock
{
	public function getName();
	public function defineContentColumn();
	public function getSettingsHtml();
	public function getBlockHtml();
}