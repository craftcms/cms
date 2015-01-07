<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class Field record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Field extends BaseRecord
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
		'uid',
		'uri',
		'url',
		'ref',
		'status',
		'title',
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
			'group' => array(static::BELONGS_TO, 'FieldGroup', 'onDelete' => static::CASCADE),
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

		// TODO: MySQL specific.
		// Field handles must be <= 58 chars so that with "field_" prepended, they're <= 64 chars (MySQL's column
		// name limit).
		$attributeConfigs['handle']['maxLength'] = 64 - strlen(Craft::$app->content->fieldColumnPrefix);

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
		return [
			'name'         => [AttributeType::Name, 'required' => true],
			'handle'       => [AttributeType::Handle, 'required' => true, 'reservedWords' => $this->reservedHandleWords],
			'context'      => [AttributeType::String, 'default' => 'global', 'required' => true],
			'instructions' => [AttributeType::String, 'column' => ColumnType::Text],
			'translatable' => AttributeType::Bool,
			'type'         => [AttributeType::ClassName, 'required' => true],
			'settings'     => AttributeType::Mixed,
		];
	}
}
