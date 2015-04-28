<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\db\Query;
use craft\app\db\QueryAbortedException;
use craft\app\elements\Entry;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DbHelper;
use craft\app\models\EntryType;
use craft\app\models\Section;
use craft\app\models\UserGroup;
use DateTime;

/**
 * EntryQuery represents a SELECT SQL statement for entries in a way that is independent of DBMS.
 *
 * @property DateTime|string           $before      The date/time that the resulting entries’ Post Dates must be before.
 * @property DateTime|string           $after       The date/time that the resulting entries’ Post Dates must be equal to or after.
 * @property string|string[]|Section   $section     The handle(s) of the section(s) that resulting entries must belong to.
 * @property string|string[]|EntryType $type        The handle(s) of the entry type(s) that resulting entries must have.
 * @property string|string[]|UserGroup $authorGroup The handle(s) of the user group(s) that resulting entries’ authors must belong to.
 *
 * @method Entry[]|array all($db=null)
 * @method Entry|array|null one($db=null)
 * @method Entry|array|null nth($n,$db=null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryQuery extends ElementQuery
{
	// Properties
	// =========================================================================

	// General parameters
	// -------------------------------------------------------------------------

	/**
	 * @inheritdoc
	 */
	public $status = 'live';

	/**
	 * @var boolean Whether to only return entries that the user has permission to edit.
	 */
	public $editable;

	/**
	 * @var integer|integer[] The section ID(s) that the resulting entries must be in.
	 */
	public $sectionId;

	/**
	 * @var integer|integer[] The entry type ID(s) that the resulting entries must have.
	 */
	public $typeId;

	/**
	 * @var integer|integer[] The user ID(s) that the resulting entries’ authors must have.
	 */
	public $authorId;

	/**
	 * @var integer|integer[] The user group ID(s) that the resulting entries’ authors must be in.
	 */
	public $authorGroupId;

	/**
	 * @var mixed The Post Date that the resulting entries must have.
	 */
	public $postDate;

	/**
	 * @var mixed The Expiry Date that the resulting entries must have.
	 */
	public $expiryDate;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'section':
			{
				$this->section($value);
				break;
			}
			case 'type':
			{
				$this->type($value);
				break;
			}
			case 'authorGroup':
			{
				$this->authorGroup($value);
				break;
			}
			case 'before':
			{
				$this->before($value);
				break;
			}
			case 'after':
			{
				$this->after($value);
				break;
			}
			default:
			{
				parent::__set($name, $value);
			}
		}
	}

	/**
	 * Sets the [[editable]] property.
	 * @param boolean $value The property value (defaults to true)
	 * @return static The query object itself
	 */
	public function editable($value = true)
	{
		$this->editable = $value;
		return $this;
	}

	/**
	 * Sets the [[sectionId]] property based on a given section(s)’s handle(s).
	 * @param string|string[]|Section $value The property value
	 * @return static The query object itself
	 */
	public function section($value)
	{
		if ($value instanceof Section)
		{
			$this->structureId = ($value->structureId ?: false);
			$this->sectionId = $value->id;
		}
		else
		{
			$query = new Query();
			$this->sectionId = $query
				->select('id')
				->from('{{%sections}}')
				->where(DbHelper::parseParam('handle', $value, $query->params))
				->column();
		}

		return $this;
	}

	/**
	 * Sets the [[sectionId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function sectionId($value)
	{
		$this->sectionId = $value;
		return $this;
	}

	/**
	 * Sets the [[typeId]] property based on a given entry type(s)’s handle(s).
	 * @param string|string[]|EntryType $value The property value
	 * @return static The query object itself
	 */
	public function type($value)
	{
		if ($value instanceof EntryType)
		{
			$this->typeId = $value->id;
		}
		else
		{
			$query = new Query();
			$this->typeId = $query
				->select('id')
				->from('{{%entrytypes}}')
				->where(DbHelper::parseParam('handle', $value, $query->params))
				->column();
		}

		return $this;
	}

	/**
	 * Sets the [[typeId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function typeId($value)
	{
		$this->typeId = $value;
		return $this;
	}

	/**
	 * Sets the [[authorId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function authorId($value)
	{
		$this->authorId = $value;
		return $this;
	}

	/**
	 * Sets the [[authorGroupId]] property based on a given user group(s)’s handle(s).
	 * @param string|string[]| $value The property value
	 * @return static The query object itself
	 */
	public function authorGroup($value)
	{
		if ($value instanceof UserGroup)
		{
			$this->authorGroupId = $value->id;
		}
		else
		{
			$query = new Query();
			$this->authorGroupId = $query
				->select('id')
				->from('{{%usergroups}}')
				->where(DbHelper::parseParam('handle', $value, $query->params))
				->column();
		}

		return $this;
	}

	/**
	 * Sets the [[authorGroupId]] property.
	 * @param integer|integer[] $value The property value
	 * @return static The query object itself
	 */
	public function authorGroupId($value)
	{
		$this->authorGroupId = $value;
		return $this;
	}

	/**
	 * Sets the [[postDate]] property.
	 * @param mixed $value The property value
	 * @return static The query object itself
	 */
	public function postDate($value)
	{
		$this->postDate = $value;
		return $this;
	}

	/**
	 * Sets the [[postDate]] property to only allow entries whose Post Date is before the given value.
	 * @param DateTime|string $value The property value
	 * @return static The query object itself
	 */
	public function before($value)
	{
		if ($value instanceof DateTime)
		{
			$value = $value->format(DateTime::W3C);
		}

		$this->postDate = ArrayHelper::toArray($this->postDate);
		$this->postDate[] = '<'.$value;
		return $this;
	}

	/**
	 * Sets the [[postDate]] property to only allow entries whose Post Date is after the given value.
	 * @param DateTime|string $value The property value
	 * @return static The query object itself
	 */
	public function after($value)
	{
		if ($value instanceof DateTime)
		{
			$value = $value->format(DateTime::W3C);
		}

		$this->postDate = ArrayHelper::toArray($this->postDate);
		$this->postDate[] = '>='.$value;
		return $this;
	}

	/**
	 * Sets the [[expiryDate]] property.
	 * @param mixed $value The property value
	 * @return static The query object itself
	 */
	public function expiryDate($value)
	{
		$this->expiryDate = $value;
		return $this;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare()
	{
		// See if 'section', 'type', or 'authorGroup' were set to invalid handles
		if ($this->sectionId === [] || $this->typeId === [] || $this->authorGroupId === [])
		{
			return false;
		}

		$this->joinElementTable('entries');

		$this->query->select([
			'entries.sectionId',
			'entries.typeId',
			'entries.authorId',
			'entries.postDate',
			'entries.expiryDate',
		]);

		if ($this->postDate)
		{
			$this->subQuery->andWhere(DbHelper::parseDateParam('entries.postDate', $this->postDate, $this->subQuery->params));
		}

		if ($this->expiryDate)
		{
			$this->subQuery->andWhere(DbHelper::parseDateParam('entries.expiryDate', $this->expiryDate, $this->subQuery->params));
		}

		if ($this->typeId)
		{
			$this->subQuery->andWhere(DbHelper::parseParam('entries.typeId', $this->typeId, $this->subQuery->params));
		}

		if (Craft::$app->getEdition() >= Craft::Client)
		{
			if ($this->authorId)
			{
				$this->subQuery->andWhere(DbHelper::parseParam('entries.authorId', $this->authorId, $this->subQuery->params));
			}

			if ($this->authorGroupId)
			{
				$this->subQuery
					->innerJoin('{{%usergroups_users}} usergroups_users', 'usergroups_users.userId = entries.authorId')
					->andWhere(DbHelper::parseParam('usergroups_users.groupId', $this->authorGroupId, $this->subQuery->params));
			}
		}

		$this->_applyEditableParam();
		$this->_applySectionIdParam();
		$this->_applyRefParam();

		if ($this->orderBy === null && !$this->structureId)
		{
			$this->orderBy = 'postDate desc';
		}

		return parent::beforePrepare();
	}

	// Private Methods
	// =========================================================================

	/**
	 * Applies the 'editable' param to the query being prepared.
	 *
	 * @throws QueryAbortedException
	 */
	private function _applyEditableParam()
	{
		if ($this->editable)
		{
			$user = Craft::$app->getUser()->getIdentity();

			if (!$user)
			{
				throw new QueryAbortedException();
			}

			// Limit the query to only the sections the user has permission to edit
			$editableSectionIds = Craft::$app->getSections()->getEditableSectionIds();
			$this->subQuery->andWhere(['in', 'entries.sectionId', $editableSectionIds]);

			// Enforce the editPeerEntries permissions for non-Single sections
			$noPeerConditions = [];

			foreach (Craft::$app->getSections()->getEditableSections() as $section)
			{
				if (
					$section->type != Section::TYPE_SINGLE &&
					!$user->can('editPeerEntries:'.$section->id)
				)
				{
					$noPeerConditions[] = ['or', 'entries.sectionId != '.$section->id, 'entries.authorId = '.$user->id];
				}
			}

			if ($noPeerConditions)
			{
				array_unshift($noPeerConditions, 'and');
				$this->subQuery->andWhere($noPeerConditions);
			}
		}
	}

	/**
	 * Applies the 'sectionId' param to the query being prepared.
	 */
	private function _applySectionIdParam()
	{
		if ($this->sectionId)
		{
			// Should we set the structureId param?
			if ($this->structureId === null && (!is_array($this->sectionId) || count($this->sectionId) === 1))
			{
				$query = new Query();
				$this->structureId = $query
					->select('structureId')
					->from('{{%sections}}')
					->where(DbHelper::parseParam('id', $this->sectionId, $query->params))
					->scalar();
			}

			$this->subQuery->andWhere(DbHelper::parseParam('entries.sectionId', $this->sectionId, $this->subQuery->params));
		}
	}

	/**
	 * Applies the 'ref' param to the query being prepared.
	 */
	private function _applyRefParam()
	{
		if ($this->ref)
		{
			$joinSections = false;
			$refs = ArrayHelper::toArray($this->ref);
			$conditionals = [];

			foreach ($refs as $ref)
			{
				$parts = array_filter(explode('/', $ref));

				if ($parts)
				{
					if (count($parts) == 1)
					{
						$conditionals[] = DbHelper::parseParam('elements_i18n.slug', $parts[0], $this->subQuery->params);
					}
					else
					{
						$conditionals[] = ['and',
							DbHelper::parseParam('sections.handle', $parts[0], $this->subQuery->params),
							DbHelper::parseParam('elements_i18n.slug', $parts[1], $this->subQuery->params)
						];
						$joinSections = true;
					}
				}
			}

			if ($conditionals)
			{
				if (count($conditionals) == 1)
				{
					$this->subQuery->andWhere($conditionals[0]);
				}
				else
				{
					array_unshift($conditionals, 'or');
					$this->subQuery->andWhere($conditionals);
				}

				if ($joinSections)
				{
					$this->subQuery->innerJoin('{{%sections}} sections', 'sections.id = entries.sectionId');
				}
			}
		}
	}
}
