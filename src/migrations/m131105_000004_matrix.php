<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131105_000004_matrix extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Create the matrixblocktypes table
		if (!craft()->db->tableExists('matrixblocktypes'))
		{
			$this->createTable('matrixblocktypes', array(
				'fieldId'       => array('column' => ColumnType::Int, 'required' => true),
				'fieldLayoutId' => array('column' => ColumnType::Int, 'required' => false),
				'name'          => array('maxLength' => 255, 'column' => ColumnType::Varchar, 'required' => true),
				'handle'        => array('maxLength' => 255, 'column' => ColumnType::Varchar, 'required' => true),
				'sortOrder'     => array('maxLength' => 4, 'column' => ColumnType::TinyInt, 'unsigned' => false),
			));
			$this->createIndex('matrixblocktypes', 'name,fieldId', true);
			$this->createIndex('matrixblocktypes', 'handle,fieldId', true);
			$this->addForeignKey('matrixblocktypes', 'fieldId', 'fields', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixblocktypes', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);
		}

		// Create the matrixblocks table
		if (!craft()->db->tableExists('matrixblocks'))
		{
			$this->createTable('matrixblocks', array(
				'id'        => array('column' => ColumnType::Int, 'required' => true, 'primaryKey' => true),
				'ownerId'   => array('column' => ColumnType::Int, 'required' => true),
				'fieldId'   => array('column' => ColumnType::Int, 'required' => true),
				'typeId'    => array('column' => ColumnType::Int, 'required' => false),
				'sortOrder' => array('maxLength' => 4, 'column' => ColumnType::TinyInt, 'unsigned' => false),
			), null, false);
			$this->createIndex('matrixblocks', 'ownerId', false);
			$this->createIndex('matrixblocks', 'fieldId', false);
			$this->createIndex('matrixblocks', 'typeId', false);
			$this->createIndex('matrixblocks', 'sortOrder', false);
			$this->addForeignKey('matrixblocks', 'id', 'elements', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixblocks', 'ownerId', 'elements', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixblocks', 'fieldId', 'fields', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixblocks', 'typeId', 'matrixblocktypes', 'id', 'CASCADE', null);
		}

		return true;
	}
}
