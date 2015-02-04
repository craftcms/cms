<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
	/**
	 * Sets the query to order by the `sortOrder` column.
	 *
	 * @return static The active query object itself.
	 */
	public function ordered()
	{
		$this->orderBy('sortOrder');
		return $this;
	}
}
