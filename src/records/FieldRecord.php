<?php
namespace Craft;

/**
 * Field record class
 */
class FieldRecord extends BaseRecord
{
	protected $reservedHandleWords = array(
		'archived',
		'author',
		'authorId',
		'children',
		'dateCreated',
		'dateUpdated',
		'enabled',
		'expiryDate',
		'handle',
		'id',
		'link',
		'img',
		'locale',
		'name',
		'parents',
		'postDate',
		'siblings',
		'type',
		'uid',
		'uri',
		'url',
		'ref',
		'status',
		'title',
	);

	private $_oldHandle;

	/**
	 * Init
	 */
	public function init()
	{
		parent::init();

		// Store the old handle in case it's ever requested.
		$this->attachEventHandler('onAfterFind', array($this, 'storeOldHandle'));
	}

	/**
	 * Store the old handle.
	 */
	public function storeOldHandle()
	{
		$this->_oldHandle = $this->handle;
	}

	/**
	 * Returns the old handle.
	 *
	 * @return string
	 */
	public function getOldHandle()
	{
		return $this->_oldHandle;
	}

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
		// Field handles must be <= 58 chars so that with "field_" prepended, they're <= 64 chars (MySQL's column name limit).
		return array(
			'name'         => array(AttributeType::Name, 'required' => true),
			'handle'       => array(AttributeType::Handle, 'maxLength' => 58, 'required' => true, 'reservedWords' => $this->reservedHandleWords),
			'context'      => array(AttributeType::String, 'default' => 'global', 'required' => true),
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
			array('columns' => array('handle', 'context'), 'unique' => true),
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
