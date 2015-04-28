<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\db;

use craft\app\base\Volume;
use craft\app\db\Query;
use craft\app\elements\Asset;
use craft\app\helpers\DbHelper;

/**
 * AssetQuery represents a SELECT SQL statement for assets in a way that is independent of DBMS.
 *
 * @property string|string[]|Volume $volume The handle(s) of the volume(s) that resulting assets must belong to.
 *
 * @method Asset[]|array all($db=null)
 * @method Asset|array|null one($db=null)
 * @method Asset|array|null nth($n,$db=null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetQuery extends ElementQuery
{
	// Properties
	// =========================================================================

	// General parameters
	// -------------------------------------------------------------------------

	/**
	 * @var integer|integer[] The volume ID(s) that the resulting assets must be in.
	 */
	public $volumeId;

	/**
	 * @var integer|integer[] The asset folder ID(s) that the resulting assets must be in.
	 */
	public $folderId;

	/**
	 * @var string|string[] The filename(s) that the resulting assets must have.
	 */
	public $filename;

	/**
	 * @var string|string[] The file kind(s) that the resulting assets must be.
	 */
	public $kind;

	/**
	 * @var integer|string The width (in pixels) that the resulting assets must have.
	 */
	public $width;

	/**
	 * @var integer|string The height (in pixels) that the resulting assets must have.
	 */
	public $height;

	/**
	 * @var integer|string The size (in bytes) that the resulting assets must have.
	 */
	public $size;

	/**
	 * @var mixed The Date Modified that the resulting assets must have.
	 */
	public $dateModified;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'volume':
			{
				$this->volume($value);
				break;
			}
			default:
			{
				parent::__set($name, $value);
			}
		}
	}

	/**
	 * Sets the [[volumeId]] property based on a given volume(s)’s handle(s).
	 *
	 * @param string|string[]|Volume $value The property value
	 * @return static The query object itself
	 */
	public function volume($value)
	{
		if ($value instanceof Volume)
		{
			$this->volumeId = $value->id;
		}
		else
		{
			$query = new Query();
			$this->volumeId = $query
				->select('id')
				->from('{{%volumes}}')
				->where(DbHelper::parseParam('handle', $value, $query->params))
				->column();
		}

		return $this;
	}

	/**
	 * Sets the [[volumeId]] property based on a given volume(s)’s handle(s).
	 *
	 * @param string|string[]|Volume $value The property value
	 * @return static The query object itself
	 * @deprecated since Craft 3.0. Use [[volume()]] instead.
	 */
	public function source($value)
	{
		Craft::$app->getDeprecator()->log('AssetQuery::source()', 'The “source” asset parameter has been deprecated. Use “volume” instead.');
		return $this->volume($value);
	}

	/**
	 * Sets the [[volumeId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function volumeId($value)
	{
		$this->volumeId = $value;
		return $this;
	}

	/**
	 * Sets the [[volumeId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 * @deprecated since Craft 3.0. Use [[volumeId()]] instead.
	 */
	public function sourceId($value)
	{
		Craft::$app->getDeprecator()->log('AssetQuery::sourceId()', 'The “sourceId” asset parameter has been deprecated. Use “volumeId” instead.');
		return $this->volumeId($value);
	}

	/**
	 * Sets the [[folderId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function folderId($value)
	{
		$this->folderId = $value;
		return $this;
	}

	/**
	 * Sets the [[filename]] property.
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function filename($value)
	{
		$this->filename = $value;
		return $this;
	}

	/**
	 * Sets the [[kind]] property.
	 * @param string|string[] $value The property value
	 * @return static The query object itself
	 */
	public function kind($value)
	{
		$this->kind = $value;
		return $this;
	}

	/**
	 * Sets the [[width]] property.
	 * @param integer|string $value The property value
	 * @return static The query object itself
	 */
	public function width($value)
	{
		$this->width = $value;
		return $this;
	}

	/**
	 * Sets the [[height]] property.
	 * @param integer|string $value The property value
	 * @return static The query object itself
	 */
	public function height($value)
	{
		$this->height = $value;
		return $this;
	}

	/**
	 * Sets the [[size]] property.
	 * @param integer|string $value The property value
	 * @return static The query object itself
	 */
	public function size($value)
	{
		$this->size = $value;
		return $this;
	}

	/**
	 * Sets the [[dateModified]] property.
	 * @param mixed $value The property value
	 * @return static The query object itself
	 */
	public function dateModified($value)
	{
		$this->dateModified = $value;
		return $this;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare()
	{
		// See if 'source' was set to an invalid handle
		if ($this->volumeId === [])
		{
			return false;
		}

		$this->joinElementTable('assets');

		$this->query->select([
			'assets.volumeId',
			'assets.folderId',
			'assets.filename',
			'assets.kind',
			'assets.width',
			'assets.height',
			'assets.size',
			'assets.dateModified',
		]);

		if ($this->volumeId)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('assets.volumeId', $this->volumeId, $this->subQuery->params));
		}

		if ($this->folderId)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('assets.folderId', $this->folderId, $this->subQuery->params));
		}

		if ($this->filename)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('assets.filename', $this->filename, $this->subQuery->params));
		}

		if ($this->kind)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('assets.kind', $this->kind, $this->subQuery->params));
		}

		if ($this->width)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('assets.width', $this->width, $this->subQuery->params));
		}

		if ($this->height)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('assets.height', $this->height, $this->subQuery->params));
		}

		if ($this->size)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('assets.size', $this->size, $this->subQuery->params));
		}

		if ($this->dateModified)
		{
			$this->subQuery->andWhere(DbHelper::parseDateParam('assets.dateModified', $this->dateModified, $this->subQuery->params));
		}

		return parent::beforePrepare();
	}
}
