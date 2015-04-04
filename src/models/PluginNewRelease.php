<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Stores the info for a plugin release.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PluginNewRelease extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string Version
	 */
	public $version;

	/**
	 * @var \DateTime Date
	 */
	public $date;

	/**
	 * @var string Notes
	 */
	public $notes;

	/**
	 * @var boolean Critical
	 */
	public $critical = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['date'], 'craft\\app\\validators\\DateTime'],
			[['version', 'date', 'notes', 'critical'], 'safe', 'on' => 'search'],
		];
	}
}
