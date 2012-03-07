<?php
namespace Blocks;

/**
 *
 */
class EmailAddress
{
	private $_email;
	private $_name;

	/**
	 * @param $email
	 * @param $name
	 */
	function __construct($email, $name = null)
	{
		$this->_email = $email;
		$this->_name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getEmailAddress()
	{
		return $this->_email;
	}

	/**
	 * @return string|null
	 */
	public function getName()
	{
		return $this->_name;
	}
}
