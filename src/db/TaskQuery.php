<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use creocoder\nestedsets\NestedSetsQueryBehavior;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TaskQuery extends \yii\db\ActiveQuery
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			NestedSetsQueryBehavior::className(),
		];
	}
}
