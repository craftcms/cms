<?php
namespace Blocks;

/**
 *
 */
abstract class Plugin extends Component
{
	public $name;
	public $version;

	public $installed = false;
	public $enabled = false;
}
