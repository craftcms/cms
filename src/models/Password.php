<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Model;

/**
 * Class Password model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Password extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string Password
	 */
	public $password;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['password'], 'required'],
			[['password'], 'string', 'min' => 6],
			[['password'], 'string', 'max' => 160],
			[['password'], 'safe', 'on' => 'search'],
		];
	}
}
