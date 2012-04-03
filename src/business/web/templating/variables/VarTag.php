<?php
namespace Blocks;

/**
 *
 */
abstract class VarTag
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
