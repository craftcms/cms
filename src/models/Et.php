<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\helpers\JsonHelper;

/**
 * Class Et model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Et extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string License key
	 */
	public $licenseKey;

	/**
	 * @var string License key status
	 */
	public $licenseKeyStatus;

	/**
	 * @var string Licensed edition
	 */
	public $licensedEdition;

	/**
	 * @var string Licensed domain
	 */
	public $licensedDomain;

	/**
	 * @var boolean Edition testable domain
	 */
	public $editionTestableDomain = false;

	/**
	 * @var array Data
	 */
	public $data;

	/**
	 * @var string Request URL
	 */
	public $requestUrl = '';

	/**
	 * @var string Request ip
	 */
	public $requestIp = '1.1.1.1';

	/**
	 * @var \DateTime Request time
	 */
	public $requestTime = '2015-03-03 22:09:04';

	/**
	 * @var string Request port
	 */
	public $requestPort;

	/**
	 * @var string Local version
	 */
	public $localVersion;

	/**
	 * @var integer Local build
	 */
	public $localBuild;

	/**
	 * @var string Local edition
	 */
	public $localEdition;

	/**
	 * @var string User email
	 */
	public $userEmail;

	/**
	 * @var string Track
	 */
	public $track;

	/**
	 * @var array Errors
	 */
	public $errors;

	/**
	 * @var array Server info
	 */
	public $serverInfo;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['licensedEdition'], 'in', 'range' => [0, 1, 2]],
			[['requestTime'], 'craft\\app\\validators\\DateTime'],
			[['localBuild'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['localVersion', 'localBuild', 'localEdition'], 'required'],
			[['licenseKey', 'licenseKeyStatus', 'licensedEdition', 'licensedDomain', 'editionTestableDomain', 'data', 'requestUrl', 'requestIp', 'requestTime', 'requestPort', 'localVersion', 'localBuild', 'localEdition', 'userEmail', 'track', 'errors'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * @return null
	 */
	public function decode()
	{
		echo JsonHelper::decode($this);
	}
}
