<?php
namespace Blocks;

/**
 *
 */
class EntryContentRecord extends BaseRecord
{
	private static $_models = array();

	private $_section;
	private $_md;

	/**
	 * Constructor
	 *
	 * @param SectionPackage $section
	 * @param string         $scenario
	 */
	public function __construct(SectionPackage $section, $scenario = 'insert')
	{
		$this->_section = $section;

		parent::__construct($scenario);
	}

	public function getTableName()
	{
		return static::getTableNameForSection($this->_section);
	}

	/**
	 * Returns the table name for an entry content table.
	 * (lame that this can't also be called getTableName() -- see https://bugs.php.net/bug.php?id=40837)
	 *
	 * @static
	 * @param SectionPackage $section
	 * @return string
	 */
	public static function getTableNameForSection(SectionPackage $section)
	{
		return 'entrycontent_'.$section->handle;
	}

	public function defineAttributes()
	{
		$attributes = array(
			'language' => array(AttributeType::Language, 'required' => true),
		);

		$blocks = blx()->entryBlocks->getBlocksBySectionId($this->_section->id);
		foreach ($blocks as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$attribute = $blockType->defineContentAttribute();
			$attribute['label'] = $block->name;

			// Required?
			if ($block->required)
			{
				$attribute['required'] = true;
			}

			$attributes[$block->handle] = $attribute;
		}

		return $attributes;
	}

	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('language', 'entryId'), 'unique' => true),
		);
	}

	public function rules()
	{
		$rules = parent::rules();

		foreach ($rules as $index => $rule)
		{
			if ($rule[1] == 'Blocks\CompositeUniqueValidator')
			{
				array_splice($rules, $index, 1);
			}
		}

		return $rules;
	}

	// -------------------------------------------------------------------------
	//  Keep the section reference when creating new instances off of this one
	// -------------------------------------------------------------------------

	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param SectionPackage $section
	 * @return EntryContentRecord
	 */
	public static function model(SectionPackage $section)
	{
		if (isset(static::$_models['EntryContentRecord'][$section->handle]))
		{
			return static::$_models['EntryContentRecord'][$section->handle];
		}
		else
		{
			$model = static::$_models['EntryContentRecord'][$section->handle] = new EntryContentRecord($section, null);
			$model->_md = new \CActiveRecordMetaData($model);
			$model->attachBehaviors($model->behaviors());
			return $model;
		}
	}

	/**
	 * Returns the meta-data for this AR.
	 *
	 * @return \CActiveRecordMetaData
	 */
	public function getMetaData()
	{
		if ($this->_md !== null)
		{
			return $this->_md;
		}
		else
		{
			return $this->_md = self::model($this->_section)->_md;
		}
	}

	/**
	 * Refreshes the meta data for this AR class.
	 */
	public function refreshMetaData()
	{
		$finder = static::model($this->_section);
		$finder->_md = new \CActiveRecordMetaData($finder);
		if ($this !== $finder)
		{
			$this->_md = $finder->_md;
		}
	}

	/**
	 * Creates an active record instance.
	 *
	 * @param array $attributes
	 * @return EntryContentRecord
	 */
	protected function instantiate($attributes)
	{
		return new EntryContentRecord($this->_section, null);
	}
}
