<?php
namespace Blocks;

/**
 * Component package class
 *
 * Used for transporting component data throughout the system.
 *
 * @abstract
 */
abstract class BaseComponentPackage extends BasePackage
{
	public $type;
	public $settings;
	public $settingsErrors;
}
