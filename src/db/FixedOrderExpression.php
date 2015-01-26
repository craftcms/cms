<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\db;

use Craft;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\web\ErrorHandler;

/**
 * FixedOrderExpression represents the SQL used to apply a fixed order to a DB result.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FixedOrderExpression extends Expression
{
	// Properties
	// =========================================================================

	/**
	 * @var string The column name that contains the values.
	 */
	public $column;

	/**
	 * @var array The column values, in the order in which the rows should be returned in.
	 */
	public $values;

	/**
	 * @var Connection The database connection.
	 */
	public $db;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param string $column The column name that contains the values.
	 * @param string $values The column values, in the order in which the rows should be returned in.
	 * @param array  $config Name-value pairs that will be used to initialize the object properties.
	 */
	public function __construct($column, $values, $config = [])
	{
		$this->column = $column;
		$this->values = $values;
		parent::__construct($config);
	}

	/**
	 * String magic method.
	 *
	 * @return string The DB expression.
	 */
	public function __toString()
	{
		if ($this->db === null)
		{
			$e = new InvalidConfigException('The "db" configuration for the FixedOrderExpression is required.');
			ErrorHandler::convertExceptionToError($e);
			return '';
		}

		return $this->db->getQueryBuilder()->fixedOrder($this->column, $this->values);
	}
}
