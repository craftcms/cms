<?php
namespace Blocks;

/**
 * Plugin interface
 */
interface IPlugin
{
	public function getName();
	public function getVersion();
	public function getDeveloper();
	public function getDeveloperUrl();
	public function isInstalled();
}