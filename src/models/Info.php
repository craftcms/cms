<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Class Info model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Info extends Model
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function populateModel($model, $config)
	{
		// Make sure $edition is going to be an integer
		if (isset($config['edition']))
		{
			$config['edition'] = (int) $config['edition'];
		}

		parent::populateModel($model, $config);
	}

	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var string Version
	 */
	public $version = '0';

	/**
	 * @var integer Build
	 */
	public $build = '0';

	/**
	 * @var string Schema version
	 */
	public $schemaVersion = '0';

	/**
	 * @var integer Edition
	 */
	public $edition = 0;

	/**
	 * @var \DateTime Release date
	 */
	public $releaseDate;

	/**
	 * @var string Site name
	 */
	public $siteName;

	/**
	 * @var string Site URL
	 */
	public $siteUrl;

	/**
	 * @var string Timezone
	 */
	public $timezone = 'America/Los_Angeles';

	/**
	 * @var boolean On
	 */
	public $on = false;

	/**
	 * @var boolean Maintenance
	 */
	public $maintenance = false;

	/**
	 * @var string Track
	 */
	public $track;

	/**
	 * @var string Uid
	 */
	public $uid;

	/**
	 * @var string Field version
	 */
	public $fieldVersion;

	/**
	 * @var \DateTime Date updated
	 */
	public $dateUpdated;

	/**
	 * @var \DateTime Date created
	 */
	public $dateCreated;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function datetimeAttributes()
	{
		$names = parent::datetimeAttributes();
		$names[] = 'releaseDate';
		return $names;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['build'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['edition'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['releaseDate'], 'craft\\app\\validators\\DateTime'],
			[['version', 'build', 'schemaVersion', 'edition', 'releaseDate', 'siteName', 'siteUrl', 'track'], 'required'],
			[['siteUrl'], 'craft\\app\\validators\\Url', 'defaultScheme' => 'http'],
			[['siteName', 'siteUrl'], 'string', 'max' => 255],
			[['timezone'], 'string', 'max' => 30],
			[['track'], 'string', 'max' => 40],
			[['id', 'version', 'build', 'schemaVersion', 'edition', 'releaseDate', 'siteName', 'siteUrl', 'timezone', 'on', 'maintenance', 'track', 'uid'], 'safe', 'on' => 'search'],
		];
	}
}
