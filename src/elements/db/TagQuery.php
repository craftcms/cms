<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\db\Query;
use craft\app\elements\Tag;
use craft\app\helpers\DbHelper;
use craft\app\models\TagGroup;
use craft\app\models\TagType;

/**
 * TagQuery represents a SELECT SQL statement for tags in a way that is independent of DBMS.
 *
 * @property string|string[]|TagGroup $group The handle(s) of the tag group(s) that resulting tags must belong to.
 *
 * @method Tag[]|array all($db=null)
 * @method Tag|array|null one($db=null)
 * @method Tag|array|null nth($n,$db=null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TagQuery extends ElementQuery
{
	// Properties
	// =========================================================================

	// General parameters
	// -------------------------------------------------------------------------

	/**
	 * @inheritdoc
	 */
	public $orderBy = 'content.title';

	/**
	 * @var integer|integer[] The tag group ID(s) that the resulting tags must be in.
	 */
	public $groupId;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'group':
			{
				$this->group($value);
				break;
			}
			case 'name':
			{
				Craft::$app->getDeprecator()->log('tag_name_param', 'Tags’ ‘name’ param has been deprecated. Use ‘title’ instead.');
				$this->title = $value;
				break;
			}
			default:
			{
				parent::__set($name, $value);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function __call($name, $params)
	{
		if ($name === 'name')
		{
			Craft::$app->getDeprecator()->log('tag_name_param', 'Tags’ ‘name’ param has been deprecated. Use ‘title’ instead.');

			if (count($params) == 1)
			{
				$this->title = $params[0];
			}
			else
			{
				$this->title = $params;
			}

			return $this;
		}
		else
		{
			return parent::__call($name, $params);
		}
	}

	/**
	 * Sets the [[groupId]] property based on a given tag group(s)’s handle(s).
	 * @param string|string[]|TagGroup $value The property value
	 * @return static The query object itself
	 */
	public function group($value)
	{
		if ($value instanceof TagGroup)
		{
			$this->groupId = $value->id;
		}
		else
		{
			$query = new Query();
			$this->groupId = $query
				->select('id')
				->from('{{%taggroups}}')
				->where(DbHelper::parseParam('handle', $value, $query->params))
				->column();
		}

		return $this;
	}

	/**
	 * Sets the [[groupId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function groupId($value)
	{
		$this->groupId = $value;
		return $this;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare()
	{
		// See if 'group' was set to an invalid handle
		if ($this->groupId === [])
		{
			return false;
		}

		$this->joinElementTable('tags');

		$this->query->select([
			'tags.groupId',
		]);

		if ($this->groupId)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('tags.groupId', $this->groupId, $this->subQuery->params));
		}

		if (is_string($this->orderBy))
		{
			$this->orderBy = preg_replace('/\bname\b/', 'title', $this->orderBy, -1, $count);

			if ($count)
			{
				Craft::$app->getDeprecator()->log('tag_orderby_name', 'Ordering tags by ‘name’ has been deprecated. Order by ‘title’ instead.');
			}
		}

		return parent::beforePrepare();
	}
}
