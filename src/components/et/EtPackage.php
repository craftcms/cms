<?php
namespace Blocks;

/**
 *
 */
class EtPackage
{
	public $url;
	public $licenseKey;
	public $licenseKeyStatus;
	public $requestIp;
	public $requestTime;
	public $requestDomain;
	public $requestPort;
	public $packages;
	public $data;
	public $errors = array();

	/**
	 * @param null $properties
	 */
	function __construct($properties = null)
	{
		if ($properties == null)
			return;

		$this->url = isset($properties['url']) ? $properties['url'] : null;
		$this->licenseKey = isset($properties['licenseKey']) ? $properties['licenseKey'] : null;
		$this->licenseKeyStatus = isset($properties['licenseKeyStatus']) ? $properties['licenseKeyStatus'] : null;
		$this->data = isset($properties['data']) ? $properties['data'] : null;
		$this->packages = isset($properties['packages']) ? $properties['packages'] : null;
		$this->requestDomain = isset($properties['requestDomain']) ? $properties['requestDomain'] : null;
		$this->requestIp = isset($properties['requestIp']) ? $properties['requestIp'] : null;
		$this->requestTime = isset($properties['requestTime']) ? $properties['requestTime'] : null;
		$this->requestPort = isset($properties['requestPort']) ? $properties['requestPort'] : null;
	}

	/*
	 *
	 */
	public function decode()
	{
		echo JsonHelper::decode($this);
	}
}
