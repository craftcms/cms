<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

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
		if ($this->orderBy !== null)
		{
			// See if we're using a fixed order
			foreach ($this->orderBy as $orderBy)
			{
				if ($orderBy instanceof FixedOrderExpression)
				{
					$orderBy->db = $builder->db;
				}
			}
		}

		return parent::prepare($builder);
	}

	/**
	 * Returns whether a given table has been joined in this query.
	 *
	 * @param string $table
	 * @return boolean
	 */
	public function isJoined($table)
	{
		foreach ($this->join as $join)
		{
			if ($join[1] === $table)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritDoc \yii\db\Query::where()
	 *
	 * @param string|array $condition
	 * @param array $params
	 * @return static
	 * @see andWhere()
	 * @see orWhere()
	 */
	public function where($conditions, $params = [])
	{
		if (!$conditions)
		{
			$conditions = null;
		}

		return parent::where($conditions, $params);
	}

	/**
	 * @inheritDoc \yii\db\Query::andWhere()
	 *
	 * @param string|array $condition
	 * @param array $params
	 * @return static
	 * @see where()
	 * @see orWhere()
	 */
	public function andWhere($conditions, $params = [])
	{
		if (!$conditions)
		{
			return $this;
		}

		return parent::andWhere($conditions, $params);
	}

	/**
	 * @inheritDoc \yii\db\Query::orWhere()
	 *
	 * @param string|array $condition
	 * @param array $params
	 * @return static
	 * @see where()
	 * @see andWhere()
	 */
	public function orWhere($conditions, $params = [])
	{
		if (!$conditions)
		{
			return $this;
		}

		return parent::orWhere($conditions, $params);
	}
}
