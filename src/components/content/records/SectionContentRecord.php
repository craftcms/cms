<?php
namespace Blocks;

/**
 *
 */
class SectionContentRecord extends BaseEntityRecord
{
	private static $_models = array();

	private $_section;
	private $_md;

	/**
	 * Constructor
	 *
	 * @param SectionModel $section
	 * @param string       $scenario
	 */
	public function __construct(SectionModel $section, $scenario = 'insert')
	{
		$this->_section = $section;

		parent::__construct($scenario);
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return blx()->sections->getSectionContentTableName($this->_section);
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		$attributes['language'] = array(AttributeType::Language, 'required' => true);
		$attributes = array_merge($attributes, parent::defineAttributes());
		return $attributes;
	}

	/**
	 * Returns the list of blocks associated with this content.
	 *
	 * @access protected
	 * @return array
	 */
	protected function getBlocks()
	{
		return blx()->sections->getBlocksBySectionId($this->_section->id);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'entry' => array(static::BELONGS_TO, 'EntryRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('language', 'entryId'), 'unique' => true),
		);
	}

	/**
	 * @return mixed
	 */
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
	 * @param SectionModel $section
	 * @return SectionContentRecord
	 */
	public static function model(SectionModel $section)
	{
		if (isset(static::$_models['SectionContentRecord'][$section->handle]))
		{
			return static::$_models['SectionContentRecord'][$section->handle];
		}
		else
		{
			$model = static::$_models['SectionContentRecord'][$section->handle] = new SectionContentRecord($section, null);
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
	 * @return SectionContentRecord
	 */
	protected function instantiate($attributes)
	{
		return new SectionContentRecord($this->_section, null);
	}
}
