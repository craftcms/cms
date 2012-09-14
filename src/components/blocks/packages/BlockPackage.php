<?php
namespace Blocks;

/**
 * Block package class.
 *
 * Used for transporting block data throughout the system.
 */
class BlockPackage
{
	public $id;
	public $name;
	public $handle;
	public $instructions;
	/* BLOCKSPRO ONLY */
	public $required = false;
	public $translatable = false;
	/* end BLOCKSPRO ONLY */
	public $class;
	public $settings;
	public $errors;
}
