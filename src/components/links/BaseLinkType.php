<?php
namespace Blocks;

/**
 * Link type base class
 */
abstract class BaseLinkType extends BaseComponent
{
	/**
	 * The type of component this is.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType = 'LinkType';

	/**
	 * Defines any link type-specific settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array();
	}
}
