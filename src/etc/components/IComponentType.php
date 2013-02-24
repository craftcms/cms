<?php
namespace Blocks;

/**
 * Component type interface
 */
interface IComponentType
{
	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getClassHandle();
}
