<?php
namespace Blocks;

/**
 * Plugin interface
 */
interface IPlugin extends IComponent
{
	public function getVersion();
	public function getDeveloper();
	public function getDeveloperUrl();
	public function isInstalled();
}