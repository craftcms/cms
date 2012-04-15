<?php
namespace Blocks;

/**
 *
 */
abstract class Adapter
{
	protected $_var;

	/**
	 * Constructor
	 *
	 * @param null $var
	 */
	function __construct($var = null)
	{
		$this->_var = $var;
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return (string)$this->_var;
	}
}
