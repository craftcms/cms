<?php
namespace Blocks;

/**
 * Component interface
 */
interface IComponent
{
	public function getName();
	public function getSettings();
	public function getSettingsHtml();
}