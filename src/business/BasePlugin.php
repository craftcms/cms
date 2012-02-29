<?php
namespace Blocks;

/**
 *
 */
abstract class BasePlugin extends BaseComponent
{
	public $name;
	public $version;

	public $installed = false;
	public $enabled = false;
}
