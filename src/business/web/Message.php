<?php
namespace Blocks;

/**
 *
 */
class Message extends Component
{
	private $_type;
	private $_key;
	private $_message;
	private $_persistent;

	/**
	 * @param      $type
	 * @param      $key
	 * @param      $message
	 * @param bool $persistent
	 */
	function __construct($type, $key, $message, $persistent = false)
	{
		$this->_key = $key;
		$this->_type = $type;
		$this->_message = $message;
		$this->_persistent = $persistent;
	}

	/**
	 * @return mixed
	 */
	public function getKey()
	{
		return $this->_key;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @return mixed
	 */
	public function getMessage()
	{
		return $this->_message;
	}

	/**
	 * @return bool
	 */
	public function isPersistent()
	{
		return $this->_persistent;
	}
}
