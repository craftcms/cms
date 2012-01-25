<?php
/**
 *
 */
class bMessage
{
	private $_status;
	private $_key;
	private $_message;
	private $_persistent;

	/**
	 * @param      $status
	 * @param      $key
	 * @param      $message
	 * @param bool $persistent
	 */
	function __construct($status, $key, $message, $persistent = false)
	{
		$this->_key = $key;
		$this->_status = $status;
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
	public function getStatus()
	{
		return $this->_status;
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
