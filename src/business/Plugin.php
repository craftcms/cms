<?php
namespace Blocks;

/**
 *
 */
abstract class Plugin extends BaseComponent
{
	public $name;
	public $version;

	public $installed = false;
	public $enabled = false;
}
