<?php
namespace Blocks;

/**
 * 
 */
class Content extends \CModel
{
	public $record;
	public $language;
	public $table;
	public $foreignKey;
	private $_content;

	/**
	 * Returns the content row
	 * @access private
	 * @return array
	 */
	private function getContent()
	{
		if (!isset($this->_content))
		{
			// Get the current content
			if (isset($this->record) && isset($this->language) && $this->record->hasContent && !$this->record->isNewRecord)
			{
				$this->_content = b()->db->createCommand()
					->from($this->table)
					->where(array($this->foreignKey => $this->record->id, 'language' => $this->language))
					->queryRow();
			}

			if (!$this->_content)
			{
				$this->_content = array('language' => $this->language);
			}
		}

		return $this->_content;
	}

	/**
	 * Sets new content
	 * @access private
	 * @param array $content
	 */
	private function setContent($content)
	{
		$this->_content = $content;
	}

	/**
	 * Getter
	 */
	function __get($name)
	{
		$blocks = $this->record->getBlocks();
		if (isset($blocks[$name]))
		{
			return $this->getValue($name);
		}
		else
			return parent::__get($name);
	}

	/**
	 * Gets the value of a content block.
	 */
	public function getValue($name)
	{
		$content = $this->getContent();
		if (isset($content[$name]))
			return $content[$name];
		else
			return null;
	}

	/**
	 * Sets the value of a content block.
	 * @param string $name
	 * @param string $value
	 */
	public function setValue($name, $value)
	{
		$content = $this->getContent();
		$content[$name] = $value;
		$this->setContent($content);
	}

	/**
	 * Sets the values of multiple content blocks at once.
	 * @param array $values
	 */
	public function setValues($values)
	{
		$content = $this->getContent();
		$content = array_merge($content, $values);
		$this->setContent($content);
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array
	 */
	public function attributeNames()
	{
		$content = $this->getContent();
		return array_keys($content);
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
		$content = $this->getContent();
		return empty($content['id']);
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
		$this->setValue($this->foreignKey, $this->record->id);

		// Insert the row
		$content = $this->getContent();
		b()->db->createCommand()->insert($this->table, $content);

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

		$content = $this->getContent();
		$id = $this->getValue('id');
		b()->db->createCommand()->update($this->table, $content, array('id' => $id));

		return true;
	}
}
