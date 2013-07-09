<?php
namespace Craft;

/**
 * Field record class
 */
class FieldRecord extends BaseRecord
{
	public $oldHandle;
	protected $reservedHandleWords = array('id', 'dateCreated', 'dateUpdated', 'uid', 'author', 'authorId', 'type', 'postDate', 'expiryDate', 'enabled', 'archived', 'locale', 'title', 'uri', 'url', 'handle', 'name');

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'fields';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'         => array(AttributeType::Name, 'required' => true),
			'handle'       => array(AttributeType::Handle, 'maxLength' => 64, 'required' => true, 'reservedWords' => $this->reservedHandleWords),
			'instructions' => array(AttributeType::String, 'column' => ColumnType::Text),
			'translatable' => AttributeType::Bool,
			'type'         => array(AttributeType::ClassName, 'required' => true),
			'settings'     => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'group' => array(static::BELONGS_TO, 'FieldGroupRecord', 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
		);
	}
}
