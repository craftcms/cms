<?php
namespace Craft;

/**
 * Class EtModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class EtModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * @return null
	 */
	public function decode()
	{
		echo JsonHelper::decode($this);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		// The client license key.
		$attributes['licenseKey'] = AttributeType::String;

		// The license key status.  Set by the server response.
		$attributes['licenseKeyStatus']  = AttributeType::String;

		// The edition that Craft is licensed to use
		$attributes['licensedEdition'] = array(AttributeType::Enum, 'values' => array(Craft::Personal, Craft::Client, Craft::Pro));

		// The domain that the license is associated with
		$attributes['licensedDomain'] = AttributeType::String;

		// Whether Craft is running for a domain that's eligible to be used in Edition Test Mode
		$attributes['editionTestableDomain'] = AttributeType::Bool;

		// The installed plugin license keys.
		$attributes['pluginLicenseKeys'] = AttributeType::Mixed;

		// The plugins' license key statuses.  Set by the server response.
		$attributes['pluginLicenseKeyStatuses'] = AttributeType::Mixed;

		// Extra arbitrary data to send to the server.
		$attributes['data'] = AttributeType::Mixed;

		// The url making the request.
		$attributes['requestUrl'] = array(AttributeType::String, 'default' => '');

		// The IP address making the request.
		$attributes['requestIp'] = array(AttributeType::String, 'default' => '1.1.1.1');

		// The time the request was made.
		$attributes['requestTime'] = array(AttributeType::DateTime, 'default' => DateTimeHelper::currentTimeForDb());

		// The port number the request comes from.
		$attributes['requestPort'] = AttributeType::String;

		// The local version number.
		$attributes['localVersion'] = array(AttributeType::String, 'required' => true);

		// The local build number.
		$attributes['localBuild'] = array(AttributeType::Number, 'required' => true);

		// The local edition.
		$attributes['localEdition'] = array(AttributeType::String, 'required' => true);

		// The currently logged in user's email address.
		$attributes['userEmail'] = AttributeType::Email;

		// The track this install is on.  Not required for backwards compatibility.
		$attributes['track'] = array(AttributeType::String);

		// Whether or not to show beta releases.
		$attributes['showBeta'] = AttributeType::Bool;

		// Any errors to return;
		$attributes['errors'] = AttributeType::Mixed;

		// Any additional server info to include.
		$attributes['serverInfo'] = AttributeType::Mixed;

		// The context of the request. Either 'craft' or a plugin handle.
		$attributes['handle'] = array(AttributeType::String, 'default' => 'craft', 'required' => true);

		return $attributes;
	}
}
