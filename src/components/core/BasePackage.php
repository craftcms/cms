<?php
namespace Blocks;

/**
 * Package base class
 *
 * Used for transporting data throughout the system.
 *
 * @abstract
 */
abstract class BasePackage
{
	public $id;
	public $errors;

	abstract public function save();
}
