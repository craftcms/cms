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
		$tableLength = strlen($table);

		foreach ($this->join as $join)
		{
			if ($join[1] === $table || strncmp($join[1], $table, $tableLength) === 0)
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

	// Add support for prepare() to throw a QueryAbortedException
	// -------------------------------------------------------------------------

	/**
	 * @inheritdoc
	 */
	public function all($db = null)
	{
		try
		{
			return parent::all($db);
		}
		catch (QueryAbortedException $e)
		{
			return [];
		}
	}

	/**
	 * @inheritdoc
	 */
	public function one($db = null)
	{
		try
		{
			return parent::one($db);
		}
		catch (QueryAbortedException $e)
		{
			return false;
		}
	}

	/**
	 * Executes the query and returns a single row of result at a given offset.
	 *
	 * @param integer    $n  The offset of the row to return. If [[offset]] is set, $offset will be added to it.
	 * @param Connection $db The database connection used to generate the SQL statement.
	 * If this parameter is not given, the `db` application component will be used.
	 * @return array|boolean The row (in terms of an array) of the query result. False is returned if the query
	 * results in nothing.
	 */
	public function nth($n, $db = null)
	{
		$offset = $this->offset;
		$this->offset = ($offset ?: 0) + $n;
		$result = $this->one($db);
		$this->offset = $offset;
		return $result;
	}

	/**
	 * @inheritdoc
	 *
	 * @param string $column The column to select. If not null, [[select]] will be temporarily overridden with this value.
	 * @param \yii\db\Connection $db The database connection used to generate the SQL statement.
	 * If this parameter is not given, the `db` application component will be used.
	 */
	public function scalar($column = null, $db = null)
	{
		if ($column)
		{
			$select = $this->select;
			$this->select = [$column];
		}

		try
		{
			$result = parent::scalar($db);
		}
		catch (QueryAbortedException $e)
		{
			$result = false;
		}

		if ($column)
		{
			$this->select = $select;
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 *
	 * @param string $column The column to select. If not null, [[select]] will be temporarily overridden with this value.
	 * @param \yii\db\Connection $db The database connection used to generate the SQL statement.
	 * If this parameter is not given, the `db` application component will be used.
	 */
	public function column($column = null, $db = null)
	{
		if ($column)
		{
			$select = $this->select;
			$this->select = [$column];
		}

		try
		{
			$result = parent::column($db);
		}
		catch (QueryAbortedException $e)
		{
			$result = [];
		}

		if ($column)
		{
			$this->select = $select;
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	protected function queryScalar($selectExpression, $db)
	{
		try
		{
			return parent::queryScalar($selectExpression, $db);
		}
		catch (QueryAbortedException $e)
		{
			return false;
		}
	}
}
