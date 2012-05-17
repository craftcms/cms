<?php
namespace Blocks;

/**
 *
 */
class EntryVersion extends BaseModel
{
	protected $tableName = 'entryversions';

	protected $attributes = array(
		'language' => AttributeType::Language,
		'draft'    => AttributeType::Boolean,
		'num'      => array('type' => AttributeType::Int, 'unsigned' => true, 'required' => true),
		'name'     => AttributeType::Name,
		'notes'    => AttributeType::TinyText,
		'changes'  => AttributeType::MediumText
	);

	protected $belongsTo = array(
		'entry'  => array('model' => 'Entry', 'required' => true),
		'author' => array('model' => 'User', 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('num','draft','entry_id'), 'unique' => true)
	);

	protected $_decodedChanges;

	/**
	 * Returns the version name
	 * @return string
	 */
	public function name()
	{
		if ($this->draft)
			return 'Draft '.$this->num;
		else
			return 'Version '.$this->num;
	}

	/**
	 * Sets the changes
	 * @param array $changes
	 */
	public function setChanges($changes)
	{
		$this->_decodedChanges = $changes;

		// Swap $changes[BlockHandle] for $changes['blocks'][BlockId]
		foreach ($this->entry->getBlocks() as $block)
		{
			if (isset($changes[$block->handle]))
			{
				$changes['blocks'][$block->id] = $changes[$block->handle];
				unset($changes[$block->handle]);
			}
		}

		$this->changes = json_encode($changes);
	}

	/**
	 * Returns the changes
	 * @return array
	 */
	public function getChanges()
	{
		if (!isset($this->_decodedChanges))
		{
			if ($this->changes)
			{
				$changes = json_decode($this->changes, true);

				// Swap $changes['blocks'][BlockId] for $changes[BlockHandle]
				foreach ($this->entry->getBlocks() as $block)
				{
					if (isset($changes['blocks'][$block->id]))
						$changes[$block->handle] = $changes['blocks'][$block->id];
				}
				unset($changes['blocks']);

				$this->_decodedChanges = $changes;
			}
			else
				$this->_decodedChanges = array();
		}

		return $this->_decodedChanges;
	}
}
