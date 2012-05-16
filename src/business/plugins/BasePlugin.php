<?php
namespace Blocks;

/**
 * Plugin base class
 */
abstract class BasePlugin extends BaseComponent
{
	public $name;
	public $version;
	public $developer;
	public $developerUrl;

	public $installed = false;

	protected $componentType = 'Plugin';
}
