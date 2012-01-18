<?php
/**
 * Modified version of IpValidator of Zend
 *
 * Copyright (c) 2005-2010, Zend Technologies USA, Inc.
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *
 *     * Neither the name of Zend Technologies USA, Inc. nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class IpValidator
{
	const INVALID        = 'ipInvalid';
	const NOT_IP_ADDRESS = 'notIpAddress';

	/**
	 * The value to be validated
	 * @var mixed
	 */
	protected $_value;

	/**
	 * @var array
	 */
	protected $_messageTemplates = array(
		self::INVALID        => "Invalid type given. String expected",
		self::NOT_IP_ADDRESS => "'%value%' does not appear to be a valid IP address",
	);

	/**
	 * internal options
	 * @var array
	 */
	protected $_options = array(
		'allowipv6' => true,
		'allowipv4' => true
	);

	/**
	 * Sets validator options
	 * @param array $options OPTIONAL Options to set, see the manual for all available options
	 * @return \IpValidator
	 */
	public function __construct($options = array())
	{
		if (!is_array($options))
		{
			$options = func_get_args();
			$temp['allowipv6'] = array_shift($options);

			if (!empty($options))
			{
				$temp['allowipv4'] = array_shift($options);
			}

			$options = $temp;
		}

		$options += $this->_options;
		$this->setOptions($options);
	}

	/**
	 * Returns all set options
	 * @return array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Sets the options for this validator
	 * @param array $options
	 * @return IpValidator
	 */
	public function setOptions($options)
	{
		if (array_key_exists('allowipv6', $options))
		{
			$this->_options['allowipv6'] = (boolean) $options['allowipv6'];
		}

		if (array_key_exists('allowipv4', $options))
		{
			$this->_options['allowipv4'] = (boolean) $options['allowipv4'];
		}

		if (!$this->_options['allowipv4'] && !$this->_options['allowipv6'])
		{
			throw new bException('Nothing to validate. Check your options');
		}

		return $this;
	}

	/**
	 * Returns true if and only if $value is a valid IP address
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		if (!is_string($value))
		{
			// invalid
			return false;
		}

		$this->_setValue($value);
		if (($this->_options['allowipv4'] && !$this->_options['allowipv6'] && !$this->_validateIPv4($value)) ||
			(!$this->_options['allowipv4'] && $this->_options['allowipv6'] && !$this->_validateIPv6($value)) ||
			($this->_options['allowipv4'] && $this->_options['allowipv6'] && !$this->_validateIPv4($value) && !$this->_validateIPv6($value)))
		{
			// not ip address
			return false;
		}

		return true;
	}

	/**
	 * Validates an IPv4 address
	 * @param string $value
	 * @return bool
	 */
	protected function _validateIPv4($value)
	{
		$ip2long = ip2long($value);

		if($ip2long === false)
		{
			return false;
		}

		return $value == long2ip($ip2long);
	}

	/**
	 * Validates an IPv6 address
	 * @param  string $value Value to check against
	 * @return boolean True when $value is a valid ipv6 address, False otherwise
	 */
	protected function _validateIPv6($value)
	{
		if (strlen($value) < 3)
		{
			return $value == '::';
		}

		if (strpos($value, '.'))
		{
			$lastColon = strrpos($value, ':');
			if (!($lastColon && $this->_validateIPv4(substr($value, $lastColon + 1))))
			{
				return false;
			}

			$value = substr($value, 0, $lastColon).':0:0';
		}

		if (strpos($value, '::') === false)
		{
			return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
		}

		$colonCount = substr_count($value, ':');
		if ($colonCount < 8)
		{
			return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
		}

		// special case with ending or starting double colon
		if ($colonCount == 8)
		{
			return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
		}

		return false;
	}

	 /**
	 * Sets the value to be validated and clears the messages and errors arrays
	 * @param  mixed $value
	 * @return void
	 */
	protected function _setValue($value)
	{
		$this->_value = $value;
	}
}
