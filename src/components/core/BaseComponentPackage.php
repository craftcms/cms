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
	public $class;
	public $settings;
	public $settingsErrors;
}
