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
		// Create the matrixrecordtypes table
		if (!craft()->db->tableExists('matrixrecordtypes'))
		{
			$this->createTable('matrixrecordtypes', array(
				'fieldId'       => array('column' => ColumnType::Int, 'required' => true),
				'fieldLayoutId' => array('column' => ColumnType::Int, 'required' => false),
				'name'          => array('maxLength' => 255, 'column' => ColumnType::Varchar, 'required' => true),
				'handle'        => array('maxLength' => 255, 'column' => ColumnType::Varchar, 'required' => true),
				'sortOrder'     => array('maxLength' => 4, 'column' => ColumnType::TinyInt, 'unsigned' => false),
			));
			$this->createIndex('matrixrecordtypes', 'name,fieldId', true);
			$this->createIndex('matrixrecordtypes', 'handle,fieldId', true);
			$this->addForeignKey('matrixrecordtypes', 'fieldId', 'fields', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixrecordtypes', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);
		}

		// Create the matrixrecords table
		if (!craft()->db->tableExists('matrixrecords'))
		{
			$this->createTable('matrixrecords', array(
				'id'        => array('column' => ColumnType::Int, 'required' => true, 'primaryKey' => true),
				'ownerId'   => array('column' => ColumnType::Int, 'required' => true),
				'fieldId'   => array('column' => ColumnType::Int, 'required' => true),
				'typeId'    => array('column' => ColumnType::Int, 'required' => false),
				'sortOrder' => array('maxLength' => 4, 'column' => ColumnType::TinyInt, 'unsigned' => false),
			), null, false);
			$this->createIndex('matrixrecords', 'ownerId', false);
			$this->createIndex('matrixrecords', 'fieldId', false);
			$this->createIndex('matrixrecords', 'typeId', false);
			$this->createIndex('matrixrecords', 'sortOrder', false);
			$this->addForeignKey('matrixrecords', 'id', 'elements', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixrecords', 'ownerId', 'elements', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixrecords', 'fieldId', 'fields', 'id', 'CASCADE', null);
			$this->addForeignKey('matrixrecords', 'typeId', 'matrixrecordtypes', 'id', 'CASCADE', null);
		}

		return true;
	}
}
