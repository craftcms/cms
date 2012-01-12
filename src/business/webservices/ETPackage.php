<?php

/**
 *
 */
class ETPackage
{
	public $licenseKeyStatus;
	public $licenseKeys;
	public $domain;
	public $edition;
	public $data;
	public $errors = array();

	/**
	 * @access public
	 *
	 * @param null $properties
	 */
	function __construct($properties = null)
	{
		if ($properties == null)
			return;

		$this->licenseKeys = isset($properties['licenseKeys']) ? $properties['licenseKeys'] : null;
		$this->licenseKeyStatus = isset($properties['licenseKeyStatus']) ? $properties['licenseKeyStatus'] : null;
		$this->data = isset($properties['data']) ? $properties['data'] : null;
		$this->domain = isset($properties['domain']) ? $properties['domain'] : null;
		$this->edition = isset($properties['edition']) ? $properties['edition'] : null;
	}

	/*
	 * @access public
	 */
	public function encodeAndEcho()
	{
		echo CJSON::encode($this);
	}

	/*
	 * @access public
	 */
	public function decode()
	{
		echo CJSON::decode($this);
	}
}
