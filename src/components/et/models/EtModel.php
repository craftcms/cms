<?php
namespace Blocks;

/**
 *
 */
class EtModel extends BaseModel
{
	//public $url;
	//public $licenseKey;
	//public $licenseKeyStatus;
	//public $requestIp;
	//public $requestTime;
	//public $requestDomain;
	//public $requestPort;
	//public $packages;
	//public $data;
	//public $errors = array();

	/**
	 * @param null $properties
	 */
	/*function __construct($properties = null)
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
	}*/

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		// The site URL as defined in blx_info->siteUrl.
		$attributes['url']      = AttributeType::String;

		// The client license key.
		$attributes['licenseKey']  = AttributeType::String;

		// The license key status.  Set by the server response.
		$attributes['licenseKeyStatus']     = AttributeType::String;

		// Extra arbitrary data to send to the server.
		$attributes['data'] = AttributeType::Mixed;

		// The domain making the request.
		$attributes['requestDomain'] = AttributeType::String;

		// The IP address making the request.
		$attributes['requestIp'] = AttributeType::String;

		// The time the request was made.
		$attributes['requestTime'] = AttributeType::DateTime;

		// The port number the request comes from.
		$attributes['requestPort'] = AttributeType::String;

		return $attributes;
	}

	/*
	 *
	 */
	public function decode()
	{
		echo JsonHelper::decode($this);
	}
}
