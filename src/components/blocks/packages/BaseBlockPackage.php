<?php
namespace Blocks;

/**
 * Block package class
 *
 * Used for transporting block data throughout the system.
 *
 * @abstract
 */
abstract class BaseBlockPackage extends BaseComponentPackage
{
	public $name;
	public $handle;
	public $instructions;
	/* BLOCKSPRO ONLY */
	public $required = false;
	public $translatable = false;
	/* end BLOCKSPRO ONLY */
}
