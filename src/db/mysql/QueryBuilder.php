<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db\mysql;

use Craft;
use yii\base\Exception;

/**
 * @inheritDoc yii\db\mysql\QueryBuilder
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class QueryBuilder extends \yii\db\mysql\QueryBuilder
{
	/**
	 * Builds the SQL expression used to return a DB result in a fixed order.
	 *
	 * @param string $column The column name that contains the values.
	 * @param string $values The column values, in the order in which the rows should be returned in.
	 * @return string The SQL expression.
	 */
	public function fixedOrder($column, $values)
	{
		$valuesSql = '';

		foreach ($values as $value)
		{
			$valuesSql .= ', '.$this->db->quoteValue($value);
		}

		return 'FIELD('.$this->quoteColumnName($column).$valuesSql.')';
	}
}
