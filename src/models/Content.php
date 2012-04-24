<?php
namespace Blocks;

/**
 * 
 */
class Content extends \CModel
{
	private $record;
	private $table;
	private $foreignKey;
	private $content;

	/**
	 * Constructor
	 * @param Model  $record
	 * @param string $language
	 */
	function __construct($record, $language)
	{
		$this->record     = $record;
		$this->table      = $this->record->getContentTableName();
		$this->foreignKey = $this->record->getForeignKeyName();

		// Get the current content
		if ($this->record->hasContent && !$this->record->isNewRecord)
		{
			$this->content = b()->db->createCommand()
				->from($this->table)
				->where(array($this->foreignKey => $this->record->id, 'language' => $language))
				->queryRow();
		}

		if (!$this->content)
		{
			$this->content = array('language' => $language);
		}
	}

	/**
	 * Getter
	 */
	function __get($name)
	{
		if (isset($this->content[$name]))
			return $this->content[$name];
		else
			return parent::__get($name);
	}

	/**
	 * Gets the value of a content block
	 */
	public function getValue($name)
	{
		if (isset($this->content[$name]))
			return $this->content[$name];
		else
			return null;
	}

	/**
	 * Sets the value of a content block
	 */
	function setValue($name, $value)
	{
		$this->content[$name] = $value;
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array
	 */
	public function attributeNames()
	{
		return array_keys($this->content);
	}

	/**
	 * Defines validation rules.
	 * @return array
	 */
	public function rules()
	{
		if (!isset($this->record))
			return $this->rules;

		$rules = array();

		foreach ($this->record->blocks as $block)
		{
			if ($block->required)
				$required[] = $block->handle;
		}

		if (!empty($required))
			$rules[] = array(implode(',', $required), 'required');

		return $rules;
	}

	/**
	 * Returns if the content is new.
	 * @return bool
	 */
	public function getIsNew()
	{
		return empty($this->content['id']);
	}

	/**
	 * Saves the content.
	 * @param bool  $runValidation Whether to perform validation before saving the content.
	 * @return bool
	 */
	public function save($runValidation = true)
	{
		if (!$runValidation || $this->validate())
			return $this->isNew ? $this->insert() : $this->update();
		else
			return false;
	}

	/**
	 * Inserts a row into the database.
	 * @return bool
	 */
	public function insert()
	{
		if (!$this->isNew)
			throw new Exception('The content row cannot be inserted into the database because it is not new.');

		if ($this->record->isNewRecord)
			throw new Exception('The content row cannot be inserted into the database before its record has been saved.');

		// Save the foreign key 
		$this->content[$this->foreignKey] = $this->record->id;

		// Insert the row
		b()->db->createCommand()->insert($this->table, $this->content);

		return true;
	}

	/**
	 * Updates the row in the database.
	 * @return bool
	 */
	public function update()
	{
		if ($this->isNew)
			throw new Exception('The content row cannot be updated because it is new.');

		b()->db->createCommand()->update($this->table, $this->content, array('id' => $this->content['id']));

		return true;
	}
}
