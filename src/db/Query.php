<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use yii\db\Expression;

/**
 * Class Query
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Query extends \yii\db\Query
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc \yii\db\Query::prepare()
	 *
	 * @param \yii\db\QueryBuilder $builder
	 * @return Query a prepared query instance which will be used by [[QueryBuilder]] to build the SQL
	 */
	public function prepare($builder)
	{
		// See if we're using a fixed order
		foreach ($this->orderBy as $orderBy)
		{
			if ($orderBy instanceof FixedOrderExpression)
			{
				$orderBy->db = $builder->db;
			}
		}

		return parent::prepare($builder);
	}
}
