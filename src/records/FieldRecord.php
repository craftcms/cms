<?php
namespace Craft;

/**
 * Field record class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.records
 * @since     1.0
 */
class FieldRecord extends BaseRecord
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	protected $reservedHandleWords = array(
		'archived',
		'children',
		'dateCreated',
		'dateUpdated',
		'enabled',
		'id',
		'link',
		'locale',
		'parents',
		'siblings',
		'sortOrder',
		'uid',
		'uri',
		'url',
		'ref',
		'status',
		'title',
		'slug'
	);

	/**
	 * @var
	 */
	private $_oldHandle;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		parent::init();

		// Store the old handle in case it's ever requested.
		$this->attachEventHandler('onAfterFind', array($this, 'storeOldHandle'));
	}

	/**
	 * Store the old handle.
	 *
	 * @return null
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
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'fields';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'group' => array(static::BELONGS_TO, 'FieldGroupRecord', 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('handle', 'context'), 'unique' => true),
			array('columns' => array('context')),
		);
	}

	/**
	 * @inheritDoc BaseRecord::scopes()
	 *
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
		);
	}

	/**
	 * Set the max field handle length based on the current field column prefix length.
	 *
	 * @return array
	 */
	public function getAttributeConfigs()
	{
		$attributeConfigs = parent::getAttributeConfigs();

		// Field handles must be <= 58 chars so that with "field_" prepended, they're <= 64 chars (MySQL's column
		// name limit).
		$attributeConfigs['handle']['maxLength'] = 64 - strlen(craft()->content->fieldColumnPrefix);

		return $attributeConfigs;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'         => array(AttributeType::Name, 'required' => true),
			'handle'       => array(AttributeType::Handle, 'required' => true, 'reservedWords' => $this->reservedHandleWords),
			'context'      => array(AttributeType::String, 'default' => 'global', 'required' => true),
			'instructions' => array(AttributeType::String, 'column' => ColumnType::Text),
			'translatable' => AttributeType::Bool,
			'type'         => array(AttributeType::ClassName, 'required' => true),
			'settings'     => AttributeType::Mixed,
		);
	}
}
