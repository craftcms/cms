<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\db;

use craft\app\base\ElementInterface;
use yii\db\Connection;
use yii\db\QueryInterface;

/**
 * ElementQueryInterface defines the common interface to be implemented by element query classes.
 *
 * The default implementation of this interface is provided by [[ElementQuery]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface ElementQueryInterface extends QueryInterface
{
	/**
	 * Configures the element query instance with a given set of parameters.
	 *
	 * @param array $criteria The criteria parameters to configure the element query with
	 * @return static The query object itself
	 */
	public function configure($criteria);

	/**
	 * Sets the [[asArray]] property.
	 * @param boolean $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function asArray($value = true);

	/**
	 * Sets the [[id]] property.
	 * @param mixed $value The property value
	 * @return static The query object itself
	 */
	public function id($value);

	/**
	 * Sets the [[fixedOrder]] property.
	 * @param boolean $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function fixedOrder($value = true);

	/**
	 * Sets the [[status]] property.
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function status($value);

	/**
	 * Sets the [[archived]] property.
	 * @param boolean $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function archived($value = true);

	/**
	 * Sets the [[dateCreated]] property.
	 * @param mixed $value The property value
	 * @return static The query object itself
	 */
	public function dateCreated($value = true);

	/**
	 * Sets the [[dateUpdated]] property.
	 * @param mixed $value The property value
	 * @return static The query object itself
	 */
	public function dateUpdated($value = true);

	/**
	 * Sets the [[locale]] property.
	 * @param string $value The property value
	 * @return static The query object itself
	 */
	public function locale($value);

	/**
	 * Sets the [[localeEnabled]] property.
	 * @param mixed $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function localeEnabled($value = true);

	/**
	 * Sets the [[relatedTo]] property.
	 * @param integer|array|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function relatedTo($value);

	/**
	 * Sets the [[title]] property.
	 * @param string $value The property value
	 * @return static The query object itself
	 */
	public function title($value);

	/**
	 * Sets the [[slug]] property.
	 * @param string $value The property value
	 * @return static The query object itself
	 */
	public function slug($value);

	/**
	 * Sets the [[uri]] property.
	 * @param string $value The property value
	 * @return static The query object itself
	 */
	public function uri($value);

	/**
	 * Sets the [[search]] property.
	 * @param string $value The property value
	 * @return static The query object itself
	 */
	public function search($value);

	/**
	 * Sets the [[ref]] property.
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function ref($value);

	/**
	 * Sets the [[structureId]] property.
	 * @param integer $value The property value
	 * @return static The query object itself
	 */
	public function structureId($value);

	/**
	 * Sets the [[level]] property.
	 * @param integer $value The property value
	 * @return static The query object itself
	 */
	public function level($value);

	/**
	 * Sets the [[ancestorOf]] property.
	 * @param integer|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function ancestorOf($value);

	/**
	 * Sets the [[ancestorDist]] property.
	 * @param integer $value The property value
	 * @return static The query object itself
	 */
	public function ancestorDist($value);

	/**
	 * Sets the [[descendantOf]] property.
	 * @param integer|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function descendantOf($value);

	/**
	 * Sets the [[descendantDist]] property.
	 * @param integer $value The property value
	 * @return static The query object itself
	 */
	public function descendantDist($value);

	/**
	 * Sets the [[siblingOf]] property.
	 * @param integer|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function siblingOf($value);

	/**
	 * Sets the [[prevSiblingOf]] property.
	 * @param integer|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function prevSiblingOf($value);

	/**
	 * Sets the [[nextSiblingOf]] property.
	 * @param integer|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function nextSiblingOf($value);

	/**
	 * Sets the [[positionedBefore]] property.
	 * @param integer|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function positionedBefore($value);

	/**
	 * Sets the [[positionedAfter]] property.
	 * @param integer|ElementInterface $value The property value
	 * @return static The query object itself
	 */
	public function positionedAfter($value);

	// Query preparation/execution
	// -------------------------------------------------------------------------

	/**
	 * Executes the query and returns all results as an array.
	 * @param Connection $db The database connection used to generate the SQL statement.
	 * If this parameter is not given, the `db` application component will be used.
	 * @return ElementInterface[]|Element[] The resulting elements.
	 */
	public function all($db = null);

	/**
	 * @inheritdoc
	 * @return ElementInterface|Element|null The resulting element.
	 */
	public function one($db = null);

	/**
	 * @inheritdoc
	 * @return ElementInterface|Element|null The resulting element.
	 */
	public function nth($n, $db = null);

	/**
	 * Executes the query and returns the IDs of the resulting elements.
	 * @param Connection $db The database connection used to generate the SQL statement.
	 * If this parameter is not given, the `db` application component will be used.
	 * @return integer[] The resulting element IDs. An empty array is returned if no elements are found.
	 */
	public function ids($db = null);
}
