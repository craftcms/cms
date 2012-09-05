<?php
namespace Blocks;

/**
 *
 */
class EntryVersionRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'entryversions';
	}

	protected function getProperties()
	{
		return array(
			'language' => PropertyType::Language,
			'draft'    => PropertyType::Boolean,
			'num'      => array(PropertyType::Int, 'unsigned' => true, 'required' => true),
			'name'     => PropertyType::Name,
			'notes'    => PropertyType::TinyText,
			'changes'  => PropertyType::MediumText
		);
	}

	protected function getRelations()
	{
		return array(
			'entry'  => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
			'author' => array(static::BELONGS_TO, 'User', 'required' => true)
		);
	}

	protected function getIndexes()
	{
		return array(
			array('columns' => array('num','draft','entryId'), 'unique' => true)
		);
	}

	protected $_decodedChanges;

	/**
	 * Returns the version name
	 *
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
	 *
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
	 *
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
