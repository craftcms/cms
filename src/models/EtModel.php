<?php
namespace Craft;

/**
 *
 */
class EtModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		// The site URL as defined in craft_info->siteUrl.
		$attributes['url'] = AttributeType::String;

		// The client license key.
		$attributes['licenseKey'] = AttributeType::String;

		// The license key status.  Set by the server response.
		$attributes['licenseKeyStatus']  = AttributeType::String;

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

		// Any packages installed on the client.
		$attributes['installedPackages'] = AttributeType::String;

		// The local version number.
		$attributes['localVersion'] = AttributeType::String;

		// The local build number.
		$attributes['localBuild'] = AttributeType::String;

		// Any errors to return;
		$attributes['errors'] = AttributeType::Mixed;

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
