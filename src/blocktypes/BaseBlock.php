<?php
namespace Blocks;

abstract class BaseBlock extends \CApplicationComponent
{
	public $name;
	private $_class;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Init
	 */
	public function init()
	{
	}

	/**
	 * Get the class name, sans the "Blocks" suffix
	 */
	public function getClass()
	{
		if (!isset($this->_class))
		{
			$this->_class = get_class($this);

			// Chop off the namespace
			if (substr($this->_class, 0, 7) == 'Blocks\\')
				$this->_class = substr($this->_class, 7);

			// Chop off the "Blocks" suffix
			if (substr($this->_class, -5) == 'Block')
				$this->_class = substr($this->_class, 0, -5);
		}

		return $this->_class;
	}
}
