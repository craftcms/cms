<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130305_000001_global_sets extends BaseMigration
{
	private $_globalSetNames;
	private $_globalSetHandles;

	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->_globalSetNames   = array();
		$this->_globalSetHandles = array();

		// Create the craft_globalsets table
		$this->createTable('globalsets', array(
			'name'          => array('maxLength' => 100, 'column' => ColumnType::Varchar, 'required' => true),
			'handle'        => array('maxLength' => 45, 'column' => ColumnType::Char, 'required' => true),
			'fieldLayoutId' => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => ColumnType::Int),
		));
		$this->createIndex('globalsets', 'name', true);
		$this->createIndex('globalsets', 'handle', true);
		$this->addForeignKey('globalsets', 'id', 'elements', 'id', 'CASCADE', null);
		$this->addForeignKey('globalsets', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL', null);

		// Get the Globals element ID (if there is one)
		$elementId = craft()->db->createCommand()
			->select('id')
			->from('elements')
			->where('type = "Globals"')
			->queryScalar();

		if ($elementId)
		{
			// Get the Globals field layout ID (if there is one)
			$layoutId = craft()->db->createCommand()
				->select('id')
				->from('fieldlayouts')
				->where('type = "Globals"')
				->queryScalar();

			// Create the "Globals" global set
			$this->insert('globalsets', array(
				'id'            => $elementId,
				'fieldLayoutId' => $layoutId,
				'name'          => 'Globals',
				'handle'        => 'globals',
			));

			// Swap the editglobals permission for editglobalset#
			$this->update('userpermissions',
				array('name' => 'editglobalset'.$elementId),
				array('name' => 'editglobals')
			);

			// Prepare for a Singleton named "Globals". Someone's doing it.
			$this->_globalSetNames[]   = 'Globals';
			$this->_globalSetHandles[] = 'globals';
		}
		else
		{
			// No more editglobals permission
			$this->delete('userpermissions', array('name' => 'editglobals'));
		}

		// Turn each singleton into a global set
		$singletons = craft()->db->createCommand()
			->select('id,name,fieldLayoutId')
			->from('singletons')
			->queryAll();

		foreach ($singletons as $singleton)
		{
			list($name, $handle) = $this->_generateGlobalSetNameAndHandle($singleton['name']);

			// Insert the new row in the globalsets table
			$this->insert('globalsets', array(
				'id'            => $singleton['id'],
				'fieldLayoutId' => $singleton['fieldLayoutId'],
				'name'          => $name,
				'handle'        => $handle,
			));

			// Delete any field layout tabs for this singleton
			$this->update('fieldlayoutfields', array('tabId' => null), array('layoutId' => $singleton['fieldLayoutId']));
			$this->delete('fieldlayouttabs', array('layoutId' => $singleton['fieldLayoutId']));

			// Update the permissions
			$this->update('userpermissions',
				array('name' => 'editglobalset'.$singleton['id']),
				array('name' => 'editsingleton'.$singleton['id'])
			);
		}

		// Drop the Singleton tables now
		$this->dropTable('singletons_i18n');
		$this->dropTable('singletons');

		// Update the "Globals" and "Singleton" type names to "GlobalSet"
		$typeCols = array('elements.type', 'fieldlayouts.type', 'linkcriteria.leftElementType', 'linkcriteria.rightElementType');

		foreach ($typeCols as $typeCol)
		{
			list($table, $column) = explode('.', $typeCol);

			$this->update($table,
				array($column => 'GlobalSet'),
				array('in', $column, array('Globals', 'Singleton'))
			);
		}

		return true;
	}

	/**
	 * Generates a unique global set name and handle for a given singleton's name
	 *
	 * @access private
	 * @param string $name
	 * @return array
	 */
	private function _generateGlobalSetNameAndHandle($name)
	{
		// Find a unique name
		$uniqueName = $name;

		for ($i = 1; in_array($uniqueName, $this->_globalSetNames); $i++)
		{
			$uniqueName = $name.' '.$i;
		}

		// Generate a handle based on the name
		$handle = $uniqueName;

		// Remove HTML tags
		$handle = preg_replace('/<(.*?)>/', '', $handle);

		// Make it lowercase
		$handle = strtolower($handle);

		// Convert extended ASCII characters to basic ASCII
		$handle = StringHelper::asciiString($handle);

		// Make it start and end with alphanumeric characters
		$handle = preg_replace('/^[^a-z0-9]+/', '', $handle);
		$handle = preg_replace('/[^a-z0-9]+$/', '', $handle);

		// Make it camelCase
		$words = array_filter(preg_split('/[^a-z0-9]+/', $handle));
		$handle = array_shift($words);

		foreach ($words as $word)
		{
			$handle .= ucfirst($word);
		}

		// Make it unique
		$uniqueHandle = $handle;

		for ($i = 1; in_array($uniqueHandle, $this->_globalSetHandles); $i++)
		{
			$uniqueHandle = $handle.$i;
		}

		// Remember them for the next singleton
		$this->_globalSetHandles[] = $uniqueHandle;
		$this->_globalSetNames[]   = $uniqueName;

		return array($uniqueName, $uniqueHandle);
	}
}
