<?php
namespace Craft;

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

	/**
	 * @return bool
	 */
	public function isSelectable();
}
